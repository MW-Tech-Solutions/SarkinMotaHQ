// Run with node tests/check-icons.cjs (no database or external dependencies).
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const root = path.resolve(__dirname, '..');
function walk(dir) {
    return fs.readdirSync(dir, {withFileTypes: true}).flatMap(e =>
        e.isDirectory() ? (/^(node_modules|vendor|\.git)$/.test(e.name) ? [] : walk(path.join(dir,e.name))) : [path.join(dir,e.name)]);
}
const sources = walk(root).filter(f => /\.(php|html|js|css)$/.test(f) && !/[/\\](tests|database)[/\\]/.test(f));
const css = fs.readFileSync(path.join(root,'assets/vendor/bootstrap-icons/bootstrap-icons.min.css'),'utf8');
const names = new Set();
for (const file of sources) {
    const source = fs.readFileSync(file,'utf8');
    // Decode numeric HTML entities and JS/CSS Unicode escapes before scanning.
    const decoded = source.replace(/&#(x[0-9a-f]+|\d+);/gi, (_,n) => String.fromCodePoint(n[0].toLowerCase()==='x' ? parseInt(n.slice(1),16) : +n))
        .replace(/\\u\{([0-9a-f]+)\}|\\u([0-9a-f]{4})/gi, (_,a,b) => String.fromCodePoint(parseInt(a||b,16)));
    // Copyright is legal text, not a decorative icon.
    assert(!/[\p{Extended_Pictographic}\p{Regional_Indicator}\uFE0F\u20E3]/u.test(decoded.replace(/©/g,'')), `UI emoji in ${file}`);
    assert(!/font-awesome|fontawesome|material-icons|lucide/i.test(source), `Mixed icon library in ${file}`);
    for (const name of source.match(/bi-[a-z0-9]+(?:-[a-z0-9]+)*/g) || []) {
        names.add(name);
        assert(css.includes(`.${name}::before`), `Missing icon ${name} in ${file}`);
    }
    for (const match of source.matchAll(/<(button|a)\b([^>]*?)>\s*<i\b[^>]*><\/i>\s*<\/\1>/g)) {
        assert(/aria-label=|title=/.test(match[2]), `Unlabeled icon-only control in ${file}`);
    }
}
for (const name of ['check-circle','x-circle','exclamation-triangle','info-circle']) assert(css.includes(`.bi-${name}::before`));
for (const [file,signature] of [['woff2','wOF2'],['woff','wOFF']]) {
    const font = fs.readFileSync(path.join(root,`assets/vendor/bootstrap-icons/fonts/bootstrap-icons.${file}`));
    assert.equal(font.toString('ascii',0,4),signature);
}
// Exercise actual theme controller, including every duplicated public/mobile/portal button.
const buttons = [];
for (const file of ['includes/header.php','includes/admin_sidebar.php']) {
    const source = fs.readFileSync(path.join(root,file),'utf8');
    for (const match of source.matchAll(/<button\b[^>]*data-theme-mode="(light|dark|system)"[^>]*>(.*?)<\/button>/gs)) {
        assert(match[2].includes('<span>'), 'Theme buttons must retain visible labels');
        const attributes = {'data-theme-mode':match[1]};
        buttons.push({attributes, getAttribute:k=>attributes[k], setAttribute:(k,v)=>attributes[k]=v, classList:{add(){},remove(){}}});
    }
}
assert.equal(buttons.length,9);
const attrs = {}, saved = {};
let systemDark = false, onSystemChange;
const context = {
    document:{documentElement:{setAttribute:(k,v)=>attrs[k]=v,classList:{add(){},remove(){}}},querySelectorAll:s=>s==='.theme-switcher-btn'?buttons:[],addEventListener(){}},
    localStorage:{getItem:k=>saved[k],setItem:(k,v)=>saved[k]=v},
    window:{matchMedia:()=>({matches:systemDark,addEventListener:(event,fn)=>onSystemChange=fn})}
};
vm.runInNewContext(fs.readFileSync(path.join(root,'assets/js/theme.js'),'utf8'),context);
for (const mode of ['light','dark','system']) {
    context.window.setThemeMode(mode);
    assert.equal(attrs['data-theme'],mode==='system'?'light':mode);
    for (const btn of buttons) assert.equal(btn.attributes['aria-pressed'],String(btn.attributes['data-theme-mode']===mode));
}
systemDark = true; onSystemChange(); assert.equal(attrs['data-theme'],'dark');
context.window.setThemeMode('light'); onSystemChange(); assert.equal(attrs['data-theme'],'light');
console.log(`PASS: ${sources.length} UI source files; ${names.size} valid icon classes; local fonts; 9 theme controls and OS preference changes.`);
