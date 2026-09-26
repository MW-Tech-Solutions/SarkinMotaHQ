<?php
/**
 * Upgraded Contact and Inquiries Page - Sarkin Mota HQ
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';

$errors = [];
$success_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "CSRF Token Validation Failed. Please retry.";
    } else {
        $name = sanitize_input($_POST['name']);
        $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) ? sanitize_input($_POST['email']) : '';
        $phone = sanitize_input($_POST['phone']);
        $subject = sanitize_input($_POST['subject']);
        $message = sanitize_input($_POST['message']);
        
        $booking_date = !empty($_POST['booking_date']) ? sanitize_input($_POST['booking_date']) : null;
        $booking_time = !empty($_POST['booking_time']) ? sanitize_input($_POST['booking_time']) : null;
        
        if (empty($name)) $errors[] = "Please provide your name.";
        if (empty($email)) $errors[] = "Please provide a valid email address.";
        if (empty($subject)) $errors[] = "Please provide an inquiry subject.";
        if (empty($message)) $errors[] = "Please write your message.";
        
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO contacts (name, email, phone, subject, message, booking_date, booking_time) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $email, $phone, $subject, $message, $booking_date, $booking_time]);
                
                // Add to audit logs
                $log_stmt = $pdo->prepare("INSERT INTO audit_logs (username, action, ip_address) VALUES (?, ?, ?)");
                $log_stmt->execute([$email, 'Contact/Booking Inquiries Submitted', $_SERVER['REMOTE_ADDR']]);
                
                $success_msg = "Thank you. Your inquiry has been successfully transmitted. Our consulting team will contact you shortly.";
            } catch (Exception $e) {
                $errors[] = "Failed to store inquiry. Please contact system admin.";
            }
        }
    }
}
?>



<!-- Header Banner -->
<section class="py-20 bg-slate-950 text-white relative">
    <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=1200&q=80')] bg-cover bg-center opacity-10"></div>
    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <span class="text-xs font-semibold uppercase tracking-widest text-gold-500 mb-3 block">Communications Center</span>
        <h1 class="text-4xl sm:text-5xl font-bold tracking-tight mb-4">Contact Our Offices</h1>
        <p class="max-w-xl mx-auto text-sm text-slate-400">Initiate a consultation or send estate, agricultural, and development inquiries.</p>
    </div>
</section>

<!-- Contact Form & Coordinates -->
<section class="py-24 bg-white dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Alerts -->
        <?php if (!empty($success_msg)): ?>
            <div class="mb-8 border-l-4 border-emerald-500 bg-emerald-50 dark:bg-emerald-950/20 p-4 rounded-r-md">
                <p class="text-sm font-semibold text-emerald-800 dark:text-emerald-300"><?php echo $success_msg; ?></p>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="mb-8 border-l-4 border-rose-500 bg-rose-50 dark:bg-rose-950/20 p-4 rounded-r-md">
                <ul class="list-disc pl-5 text-xs text-rose-800 dark:text-rose-300 space-y-1">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 mb-20">
            
            <!-- Contact details & information -->
            <div>
                <span class="text-xs font-semibold uppercase tracking-widest text-gold-500 mb-3 block">Office Details</span>
                <h2 class="text-3xl font-bold text-slate-900 dark:text-white mb-6">Headquarters Directory</h2>
                <p class="text-slate-550 dark:text-slate-400 text-sm leading-relaxed mb-8">
                    Our team provides consulting services throughout West Africa. Visit our headquarters in Yola or submit the contact form, and we will route your inquiry to the appropriate division.
                </p>
                
                <div class="space-y-6 mb-10">
                    <div class="flex items-start space-x-4">
                        <div class="h-10 w-10 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center text-sm text-gold-500"><i aria-hidden="true" class="bi bi-geo-alt-fill"></i></div>
                        <div>
                            <h4 class="font-bold text-slate-900 dark:text-white text-sm">Office Address</h4>
                            <p class="text-xs text-slate-500 leading-relaxed">
                                <?php echo nl2br(sanitize_input(setting('company_address', 'ADSYP 32, ATM Road, Off Jawa Street, Kofare-Agric, Jimeta-Yola, Adamawa State, Nigeria.'))); ?>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start space-x-4">
                        <div class="h-10 w-10 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center text-sm text-gold-500"><i aria-hidden="true" class="bi bi-envelope-fill"></i></div>
                        <div>
                            <h4 class="font-bold text-slate-900 dark:text-white text-sm">Email Inquiries</h4>
                            <p class="text-xs text-slate-500"><?php echo sanitize_input(setting('contact_email', 'info@sarkinmotahq.com')); ?></p>
                        </div>
                    </div>
                    <div class="flex items-start space-x-4">
                        <div class="h-10 w-10 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center text-sm text-gold-500"><i aria-hidden="true" class="bi bi-telephone-fill"></i></div>
                        <div>
                            <h4 class="font-bold text-slate-900 dark:text-white text-sm">Telephone Line</h4>
                            <p class="text-xs text-slate-500"><?php echo sanitize_input(setting('contact_phone', '+234 (0) 803 000 0000')); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Business Hours & Socials -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 bg-slate-50 dark:bg-gray-950 p-6 rounded-2xl border border-slate-200 dark:border-slate-800">
                    <div>
                        <h4 class="font-bold text-slate-900 dark:text-white text-xs uppercase tracking-wider mb-3">Business Hours</h4>
                        <ul class="text-xs text-slate-500 space-y-1">
                            <li>Weekdays: 8:00 AM - 5:00 PM</li>
                            <li>Saturdays: 9:00 AM - 1:00 PM</li>
                            <li>Sundays: Closed</li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-900 dark:text-white text-xs uppercase tracking-wider mb-3">Social Networks</h4>
                        <div class="flex space-x-3">
                            <a href="#" class="h-8 w-8 rounded bg-slate-200 dark:bg-slate-800 flex items-center justify-center text-slate-900 dark:text-white text-xs hover:bg-gold-500 transition-colors">LN</a>
                            <a href="#" class="h-8 w-8 rounded bg-slate-200 dark:bg-slate-800 flex items-center justify-center text-slate-900 dark:text-white text-xs hover:bg-gold-500 transition-colors">FB</a>
                            <a href="#" class="h-8 w-8 rounded bg-slate-200 dark:bg-slate-800 flex items-center justify-center text-slate-900 dark:text-white text-xs hover:bg-gold-500 transition-colors">TW</a>
                        </div>
                        <a href="<?php echo sanitize_input(setting('whatsapp_link', 'https://wa.me/2348030000000')); ?>" target="_blank" class="inline-block mt-4 px-4 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded text-[10px] font-bold uppercase transition-colors flex items-center justify-center space-x-1.5 max-w-max">
                            <i aria-hidden="true" class="bi bi-whatsapp"></i> <span>WhatsApp Agent</span>
                        </a>
                    </div>
                </div>

            </div>

            <!-- Message Form -->
            <div class="bg-slate-50 dark:bg-gray-950 border border-slate-200/50 dark:border-slate-800 p-6 sm:p-10 rounded-3xl shadow-lg">
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-6">Send a Message</h3>
                
                <form action="contact.php" method="POST" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-[10px] font-bold text-slate-455 uppercase tracking-widest mb-1.5">Full Name</label>
                            <input type="text" id="name" name="name" required class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg px-4 py-3 text-xs text-slate-900 dark:text-white focus:outline-none">
                        </div>
                        <div>
                            <label for="email" class="block text-[10px] font-bold text-slate-455 uppercase tracking-widest mb-1.5">Email Address</label>
                            <input type="email" id="email" name="email" required class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg px-4 py-3 text-xs text-slate-900 dark:text-white focus:outline-none">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <label for="phone" class="block text-[10px] font-bold text-slate-455 uppercase tracking-widest mb-1.5">Phone Number</label>
                            <input type="text" id="phone" name="phone" placeholder="e.g. +234..." class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg px-4 py-3 text-xs text-slate-900 dark:text-white focus:outline-none">
                        </div>
                        <div>
                            <label for="subject" class="block text-[10px] font-bold text-slate-455 uppercase tracking-widest mb-1.5">Inquiry Subject</label>
                            <input type="text" id="subject" name="subject" required placeholder="e.g. Property Consultation" class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg px-4 py-3 text-xs text-slate-900 dark:text-white focus:outline-none">
                        </div>
                    </div>

                    <div>
                        <label for="message" class="block text-[10px] font-bold text-slate-455 uppercase tracking-widest mb-1.5">Detail Message</label>
                        <textarea id="message" name="message" rows="4" required placeholder="Write your requirements here..." class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg px-4 py-3 text-xs text-slate-900 dark:text-white focus:outline-none"></textarea>
                    </div>

                    <button type="submit" class="w-full bg-gold-500 text-slate-950 font-bold uppercase tracking-wider text-xs py-3.5 rounded-lg hover:bg-gold-600 transition-colors shadow-md">
                        Transmit Brief
                    </button>
                </form>
            </div>

        </div>

        <!-- Google Maps Satellite View Office Location -->
        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-6">Headquarters & Office Location</h3>
        <div id="contact-office-map" class="h-96 sm:h-[450px] w-full rounded-3xl overflow-hidden border border-slate-200/50 dark:border-slate-800 shadow-md bg-slate-100 dark:bg-slate-900 z-10">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3940.11559561203!2d7.4753661000000005!3d9.0532195!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x104e0b007c1fa41d%3A0x75c564b5037c4ada!2sSarkinMota%20Autos!5e0!3m2!1sen!2sng!4v1790427341094!5m2!1sen!2sng" class="w-full h-full" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>
        </div>

    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
