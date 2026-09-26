<?php
/**
 * Upgraded Global Premium Footer with Centralized Branding & AI Assistant
 * Enterprise Edition — Sarkin Mota HQ
 */
$root_dir = realpath(__DIR__ . '/..');
$script_file = $_SERVER['SCRIPT_FILENAME'] ?? '';
$path_depth = '';
if (!empty($script_file)) {
    $script_dir = realpath(dirname($script_file));
    if ($root_dir && $script_dir && strpos($script_dir, $root_dir) === 0) {
        $rel = trim(substr($script_dir, strlen($root_dir)), '/\\');
        if ($rel !== '') {
            $parts = array_filter(explode('/', str_replace('\\', '/', $rel)));
            $path_depth = str_repeat('../', count($parts));
        }
    }
}
if ($path_depth === '') {
    $current_uri = $_SERVER['PHP_SELF'] ?? '';
    if (preg_match('#/(admin|auth|hr|sales|staff|divisions|error_pages)/#i', $current_uri)) {
        $path_depth = '../';
    }
}
?>

<?php 
$is_portal_view = isset($is_portal_page) && $is_portal_page === true;
if (!$is_portal_view) {
    $script_path = $_SERVER['PHP_SELF'] ?? '';
    if (strpos($script_path, '/admin/') !== false || strpos($script_path, '/hr/') !== false || strpos($script_path, 'dashboard.php') !== false) {
        $is_portal_view = true;
    }
}
if (!$is_portal_view): 
?>
<footer class="bg-slate-900 text-slate-400 dark:bg-black dark:text-slate-400 pt-12 md:pt-20 pb-10 border-t border-slate-800/80">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Grid Main Section -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 md:gap-12 mb-12 md:mb-16">
            
            <!-- Column 1: Company Profile -->
            <div>
                <a href="<?php echo $path_depth; ?>index.php" class="flex items-center space-x-3 mb-4 md:mb-6">
                    <img src="<?php echo $path_depth . htmlspecialchars(setting('company_logo_dark', setting('company_logo'))); ?>" alt="<?php echo htmlspecialchars(setting('company_name')); ?> Logo" class="h-8 md:h-10 w-auto object-contain">
                </a>
                <p class="text-xs md:text-sm leading-relaxed mb-6">
                    <?php echo htmlspecialchars(setting('company_tagline')); ?>. Operating across Agriculture, Estate & Property Management, Environmental Advisory, and Urban Development.
                </p>
                <div class="flex space-x-3">
                    <a href="#" class="h-9 w-9 rounded-full bg-slate-800 hover:bg-amber-500 hover:text-slate-950 transition-colors flex items-center justify-center text-slate-300" aria-label="LinkedIn"><i aria-hidden="true" class="bi bi-linkedin text-sm"></i></a>
                    <a href="#" class="h-9 w-9 rounded-full bg-slate-800 hover:bg-amber-500 hover:text-slate-950 transition-colors flex items-center justify-center text-slate-300" aria-label="Facebook"><i aria-hidden="true" class="bi bi-facebook text-sm"></i></a>
                    <a href="#" class="h-9 w-9 rounded-full bg-slate-800 hover:bg-amber-500 hover:text-slate-950 transition-colors flex items-center justify-center text-slate-300" aria-label="Instagram"><i aria-hidden="true" class="bi bi-instagram text-sm"></i></a>
                    <a href="#" class="h-9 w-9 rounded-full bg-slate-800 hover:bg-amber-500 hover:text-slate-950 transition-colors flex items-center justify-center text-slate-300" aria-label="YouTube"><i aria-hidden="true" class="bi bi-youtube text-sm"></i></a>
                </div>
            </div>

            <!-- Column 2: Divisions Sitemap (Accordion on Mobile) -->
            <div class="border-b border-slate-800/80 md:border-none pb-4 md:pb-0">
                <button type="button" onclick="toggleFooterAccordion('acc-divisions', this)" aria-expanded="false" aria-controls="acc-divisions" class="md:hidden flex items-center justify-between w-full py-2 text-white text-xs font-bold uppercase tracking-wider font-montserrat">
                    <span>Our Divisions</span>
                    <i class="bi bi-chevron-down accordion-icon text-slate-400 transition-transform"></i>
                </button>
                <h3 class="hidden md:block text-white text-sm font-semibold tracking-wider uppercase mb-6 font-montserrat">Our Divisions</h3>
                
                <div id="acc-divisions" class="hidden md:block pt-3 md:pt-0">
                    <ul class="space-y-3 text-xs md:text-sm">
                        <li><a href="<?php echo $path_depth; ?>divisions/agriculture.php" class="hover:text-amber-500 transition-colors flex items-center"><i class="bi bi-dot mr-2 text-emerald-500" aria-hidden="true"></i> Agriculture Consulting</a></li>
                        <li><a href="<?php echo $path_depth; ?>divisions/estate-management.php" class="hover:text-amber-500 transition-colors flex items-center"><i class="bi bi-dot mr-2 text-amber-500" aria-hidden="true"></i> Estate & Property Management</a></li>
                        <li><a href="<?php echo $path_depth; ?>divisions/environmental.php" class="hover:text-amber-500 transition-colors flex items-center"><i class="bi bi-dot mr-2 text-emerald-500" aria-hidden="true"></i> Environmental Advisory</a></li>
                        <li><a href="<?php echo $path_depth; ?>divisions/development.php" class="hover:text-amber-500 transition-colors flex items-center"><i class="bi bi-dot mr-2 text-amber-500" aria-hidden="true"></i> Development Consults</a></li>
                    </ul>
                </div>
            </div>

            <!-- Column 3: Quick Navigation (Accordion on Mobile) -->
            <div class="border-b border-slate-800/80 md:border-none pb-4 md:pb-0">
                <button type="button" onclick="toggleFooterAccordion('acc-sitemap', this)" aria-expanded="false" aria-controls="acc-sitemap" class="md:hidden flex items-center justify-between w-full py-2 text-white text-xs font-bold uppercase tracking-wider font-montserrat">
                    <span>Corporate Sitemap</span>
                    <i class="bi bi-chevron-down accordion-icon text-slate-400 transition-transform"></i>
                </button>
                <h3 class="hidden md:block text-white text-sm font-semibold tracking-wider uppercase mb-6 font-montserrat">Corporate Sitemap</h3>
                
                <div id="acc-sitemap" class="hidden md:block pt-3 md:pt-0">
                    <ul class="grid grid-cols-2 gap-x-4 gap-y-2.5 md:gap-y-3.5 text-xs md:text-sm">
                        <li><a href="<?php echo $path_depth; ?>index.php" class="hover:text-amber-500 transition-colors">Home</a></li>
                        <li><a href="<?php echo $path_depth; ?>about.php" class="hover:text-amber-500 transition-colors">About Us</a></li>
                        <li><a href="<?php echo $path_depth; ?>services.php" class="hover:text-amber-500 transition-colors">Services</a></li>
                        <li><a href="<?php echo $path_depth; ?>projects.php" class="hover:text-amber-500 transition-colors">Projects</a></li>
                        <li><a href="<?php echo $path_depth; ?>properties.php" class="hover:text-amber-500 transition-colors">Listings</a></li>
                        <li><a href="<?php echo $path_depth; ?>news.php" class="hover:text-amber-500 transition-colors">News</a></li>
                        <li><a href="<?php echo $path_depth; ?>careers.php" class="hover:text-amber-500 transition-colors">Careers</a></li>
                        <li><a href="<?php echo $path_depth; ?>contact.php" class="hover:text-amber-500 transition-colors">Contact</a></li>
                    </ul>
                </div>
            </div>

            <!-- Column 4: Contact & Office Info (Accordion on Mobile) -->
            <div>
                <button type="button" onclick="toggleFooterAccordion('acc-contact', this)" aria-expanded="false" aria-controls="acc-contact" class="md:hidden flex items-center justify-between w-full py-2 text-white text-xs font-bold uppercase tracking-wider font-montserrat">
                    <span>Head Office</span>
                    <i class="bi bi-chevron-down accordion-icon text-slate-400 transition-transform"></i>
                </button>
                <h3 class="hidden md:block text-white text-sm font-semibold tracking-wider uppercase mb-6 font-montserrat">Head Office</h3>
                
                <div id="acc-contact" class="hidden md:block pt-3 md:pt-0">
                    <p class="text-xs md:text-sm leading-relaxed mb-4 text-slate-300">
                        <i aria-hidden="true" class="bi bi-geo-alt-fill text-amber-500 mr-1"></i> <?php echo htmlspecialchars(setting('company_address')); ?>
                    </p>
                    <p class="text-xs text-slate-300 mb-2">
                        <i aria-hidden="true" class="bi bi-telephone-fill text-amber-500 mr-1"></i> <?php echo htmlspecialchars(setting('company_phone')); ?>
                    </p>
                    <p class="text-xs text-slate-300 mb-4">
                        <i aria-hidden="true" class="bi bi-envelope-fill text-amber-500 mr-1"></i> <?php echo htmlspecialchars(setting('company_email')); ?>
                    </p>
                </div>
            </div>

        </div>

        <!-- Lower Section: Legal & Credit -->
        <div class="border-t border-slate-800/60 pt-8 flex flex-col md:flex-row justify-between items-center text-xs text-slate-500 gap-4">
            <p class="text-center md:text-left">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(setting('company_name')); ?>. All rights reserved.</p>
            <div class="flex flex-wrap justify-center space-x-6">
                <a href="#" class="hover:text-slate-300 transition-colors">Privacy Policy</a>
                <a href="#" class="hover:text-slate-300 transition-colors">Terms of Service</a>
                <a href="#" class="hover:text-slate-300 transition-colors">Security Details</a>
            </div>
        </div>

    </div>
</footer>

<!-- Floating AI Chatbot Widget -->
<div class="fixed bottom-4 right-4 sm:bottom-6 sm:right-6 z-50">
    <button id="ai-chat-btn" onclick="toggleAIChat()" class="h-12 w-12 bg-amber-500 text-slate-950 rounded-full flex items-center justify-center shadow-2xl hover:scale-110 transition-transform focus:outline-none" aria-label="Toggle AI Assistant">
        <span class="text-lg"><i aria-hidden="true" class="bi bi-robot"></i></span>
    </button>
    
    <div id="ai-chat-box" class="hidden absolute bottom-16 right-0 w-[calc(100vw-32px)] sm:w-96 max-w-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-2xl overflow-hidden flex flex-col h-[380px] sm:h-[400px]">
        <div class="bg-slate-900 text-white px-4 py-3 flex justify-between items-center font-montserrat">
            <div class="flex items-center space-x-2 truncate">
                <i class="bi bi-circle-fill text-emerald-500 text-[8px]" role="img" aria-label="Online"></i>
                <span class="text-xs font-bold uppercase tracking-wider truncate"><?php echo htmlspecialchars(setting('company_short_name')); ?> AI Assistant</span>
            </div>
            <button type="button" onclick="toggleAIChat()" aria-label="Close AI assistant" class="text-slate-400 hover:text-white p-1"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
        </div>
        
        <div id="ai-messages" class="flex-1 p-4 overflow-y-auto space-y-3.5 text-xs font-sans">
            <div class="bg-slate-50 dark:bg-gray-950 p-3 rounded-2xl max-w-[85%] border border-slate-200/50 dark:border-slate-800">
                Hello. Welcome to <?php echo htmlspecialchars(setting('company_name')); ?>. How can I assist you today?
            </div>
        </div>
        
        <div class="p-3 border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-gray-950 flex items-center space-x-2">
            <input type="text" id="ai-chat-input" placeholder="Ask a question..." class="flex-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none" onkeypress="if(event.key === 'Enter') sendAIChatMessage()">
            <button onclick="sendAIChatMessage()" class="px-3 py-2 bg-amber-500 text-slate-950 rounded-lg text-xs font-bold"><i class="bi bi-send ui-icon" aria-hidden="true"></i>Send</button>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function toggleFooterAccordion(contentId, btn) {
    const content = document.getElementById(contentId);
    const icon = btn.querySelector('.accordion-icon');
    if (content) {
        const isHidden = content.classList.contains('hidden');
        if (isHidden) {
            content.classList.remove('hidden');
            btn.setAttribute('aria-expanded', 'true');
            if (icon) icon.classList.add('rotate-180');
        } else {
            content.classList.add('hidden');
            btn.setAttribute('aria-expanded', 'false');
            if (icon) icon.classList.remove('rotate-180');
        }
    }
}

function toggleAIChat() {
    const box = document.getElementById('ai-chat-box');
    if (box) box.classList.toggle('hidden');
}

function sendAIChatMessage() {
    const input = document.getElementById('ai-chat-input');
    const question = input.value.trim();
    if (!question) return;
    
    const container = document.getElementById('ai-messages');
    const userMsg = document.createElement('div');
    userMsg.className = "bg-amber-500 text-slate-950 p-3 rounded-2xl max-w-[85%] ml-auto text-right font-bold";
    userMsg.innerText = question;
    container.appendChild(userMsg);
    input.value = '';
    
    setTimeout(() => {
        const botMsg = document.createElement('div');
        botMsg.className = "bg-slate-50 dark:bg-gray-950 p-3 rounded-2xl max-w-[85%] border border-slate-200/50 dark:border-slate-800";
        botMsg.innerText = "Thank you for reaching out to " + <?php echo json_encode(setting('company_short_name')); ?> + ". An advisor will be happy to assist you.";
        container.appendChild(botMsg);
        container.scrollTop = container.scrollHeight;
    }, 600);
}
</script>

<script src="<?php echo $path_depth; ?>assets/js/main.js"></script>
</body>
</html>

