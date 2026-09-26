const fs = require('node:fs');
const path = require('node:path');
const crypto = require('node:crypto');
function walk(dir) {
  return fs.readdirSync(dir, {withFileTypes: true}).flatMap(e => e.isDirectory()
    ? (['node_modules', 'storage', '.git', '.codex', '.agents'].includes(e.name) ? [] : walk(path.join(dir,e.name)))
    : [path.join(dir,e.name).replaceAll('\\','/')]);
}
const files = walk('.');
fs.mkdirSync('storage/quality', {recursive:true});
if (!fs.existsSync('storage/quality/baseline.json')) fs.writeFileSync('storage/quality/baseline.json',JSON.stringify(Object.fromEntries(files.map(f=>[f,crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex')]))));
const routes = files.filter(f => f.endsWith('.php') && !/^(app|config|includes|database|scratch|tests|tools)\//.test(f)).map(file => {
  const src = fs.readFileSync(file,'utf8');
  return {file, guards:[...src.matchAll(/require_(?:login|role|permission|admin|super_admin|staff)\([^;]*\)/g)].map(m=>m[0]),
    actions:[...src.matchAll(/isset\(\$_POST\['([^']+)'\]\)/g)].map(m=>m[1]), forms:(src.match(/<form\b/g)||[]).length,
    tabs:[...src.matchAll(/id="(?:adm|tnt|lnd|clt)-tab-([^"\s]+)"/g)].map(m=>m[1]),
    methods:src.includes("REQUEST_METHOD") ? ['GET','POST'] : ['GET']};
});
fs.writeFileSync('docs/route-inventory.json',JSON.stringify(routes,null,2));
fs.writeFileSync('docs/route-inventory.md','# Discovered routes (source inventory)\n\nEach PHP entry point and its forms is listed before interface changes. Tab variants are included in the JSON inventory. Internal includes, CLI tools, tests, database and scratch scripts are not user routes and must be denied by the web server.\n\n| Route | Guards | Forms | POST actions |\n|---|---|---:|---|\n'+routes.map(r=>`| /${r.file} | ${r.guards.join('; ') || 'Public / inline checks'} | ${r.forms} | ${r.actions.join(', ')} |`).join('\n')+'\n');
console.log(`${files.length} project files; ${routes.length} routes; ${routes.reduce((n,r)=>n+r.forms,0)} forms.`);
