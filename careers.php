<?php
/**
 * Careers and Job Applications Portal - Sarkin Mota HQ
 * Enterprise Edition
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_helper.php';

// Form Handling
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_job'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "CSRF Token Validation Failed. Please retry.";
    } else {
        $job_id = intval($_POST['job_id'] ?? 0);
        $name = sanitize_input($_POST['name'] ?? '');
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL) ? sanitize_input($_POST['email']) : '';
        $phone = sanitize_input($_POST['phone'] ?? '');
        $cover_letter = sanitize_input($_POST['cover_letter'] ?? '');
        
        if (empty($name)) $errors[] = "Please provide your full name.";
        if (empty($email)) $errors[] = "Please provide a valid email address.";
        if (empty($phone)) $errors[] = "Please provide your phone number.";
        
        // Private File Upload Processing
        $resume_filename = handle_document_upload('resume');
        if (!$resume_filename) {
            $errors[] = "Invalid resume file. Only PDF, DOC, and DOCX files up to 10MB are permitted.";
        }
        
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO applications (job_id, name, email, phone, cover_letter, resume_path) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$job_id, $name, $email, $phone, $cover_letter, $resume_filename]);
                $application_id = $pdo->lastInsertId();

                // Dispatch Email Notification to Vacancy Recipient & Candidate Confirmation
                require_once __DIR__ . '/includes/mail_helper.php';
                $job_title = 'Job Application';
                if ($job_id > 0) {
                    $stmt_j = $pdo->prepare("SELECT title FROM careers WHERE id = ?");
                    $stmt_j->execute([$job_id]);
                    $job_title = $stmt_j->fetchColumn() ?: 'Job Application';
                }
                notify_job_application($pdo, $email, $name, $job_title, $job_id, $application_id);

                // Audit log
                $log = $pdo->prepare("INSERT INTO audit_logs (username, action, ip_address) VALUES (?, ?, ?)");
                $log->execute([$email, "Submitted Job Application for Job ID: {$job_id}", $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);

                set_flash_message('success', 'Your application has been submitted securely. Our HR team will review your credentials.');
                redirect('careers.php?applied=1');
            } catch (Exception $e) {
                error_log("Job Application Error: " . $e->getMessage());
                $errors[] = "An unexpected error occurred while saving your application.";
            }
        }
    }
}

// Fetch active open positions from database
$jobs = [];
try {
    $stmt = $pdo->query("SELECT * FROM careers WHERE status IN ('published', 'open') ORDER BY id DESC");
    $jobs = $stmt->fetchAll();
} catch (Exception $e) {
    // Fallback Mock Open Careers
    $jobs = [
        [
            'id' => 1,
            'title' => 'Senior Property Valuer',
            'department' => 'Estate & Property Management',
            'location' => 'Jimeta-Yola Office',
            'description' => 'We are seeking an RICS or NIESV-accredited valuer to coordinate property assessments and portfolio audits throughout Adamawa and neighboring states.',
            'requirements' => "• Minimum of 5 years in property valuation.\n• Certified NIESV/RICS registration.\n• Excellent reporting and client management skills."
        ],
        [
            'id' => 2,
            'title' => 'Lead Agronomy Consultant',
            'department' => 'Agriculture',
            'location' => 'Jimeta & Field Locations',
            'description' => 'Lead field agronomy consults, soil quality audits, irrigation planning, and high-yield farming crop forecasting reports.',
            'requirements' => "• B.Sc. or M.Sc. in Agronomy or Soil Science.\n• Hands-on experience with mechanized grain setups.\n• Willingness to travel to regional farming zones."
        ]
    ];
    error_log("Careers Fetch Error: " . $e->getMessage());
}

$page_title = "Careers & Vacancies — " . setting('company_short_name', 'Sarkin Mota HQ');
require_once __DIR__ . '/includes/header.php';
?>

<!-- Header Banner -->
<section class="py-16 md:py-20 bg-slate-950 text-white relative">
    <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=1200&q=80')] bg-cover bg-center opacity-10"></div>
    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <span class="text-[10px] sm:text-xs font-semibold uppercase tracking-widest text-amber-500 mb-2 sm:mb-3 block">Join Our Firm</span>
        <h1 class="text-3xl sm:text-4xl md:text-5xl font-bold tracking-tight mb-3 sm:mb-4">Careers at <?php echo htmlspecialchars(setting('company_short_name', 'Sarkin Mota HQ')); ?></h1>
        <p class="max-w-xl mx-auto text-xs sm:text-sm text-slate-400 leading-relaxed">Help shape the future of real estate assets, green initiatives, and agricultural operations across Africa.</p>
    </div>
</section>

<!-- Job Listings & Application Form -->
<section class="py-12 sm:py-16 md:py-20 bg-slate-50 dark:bg-gray-950">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <?php echo display_flash_message(); ?>

        <?php if (!empty($errors)): ?>
            <div class="mb-8 border-l-4 border-rose-500 bg-rose-50 dark:bg-rose-950/20 p-4 rounded-r-md">
                <ul class="list-disc pl-5 text-xs text-rose-800 dark:text-rose-300 space-y-1">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="text-center max-w-3xl mx-auto mb-12 md:mb-16">
            <span class="text-[10px] font-bold text-amber-500 uppercase tracking-widest block mb-2">Join Our Enterprise Team</span>
            <h2 class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-slate-900 dark:text-white">Career Opportunities</h2>
            <p class="mt-3 text-xs sm:text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                Build your career with Adamawa & Abuja's leading corporate real estate, agronomy valuation, environmental consulting, and infrastructure firm.
            </p>
        </div>

        <!-- Open Vacancies Grid & Form Container -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 md:gap-12 items-start">
            
            <!-- Open Positions List -->
            <div class="lg:col-span-2 space-y-6 sm:space-y-8">
                <h3 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white mb-4 sm:mb-6">Open Positions</h3>
                
                <?php if (empty($jobs)): ?>
                    <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 rounded-3xl p-8 text-center text-slate-400">
                        <i aria-hidden="true" class="bi bi-briefcase text-3xl mb-2 block text-slate-300"></i>
                        <p class="text-xs">There are no open job vacancies at this time. Please check back later.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($jobs as $job): ?>
                        <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-sm flex flex-col justify-between luxury-card">
                            <div class="mb-6">
                                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                                    <div>
                                        <span class="text-[9px] font-bold uppercase tracking-widest text-amber-500 block mb-1"><?php echo htmlspecialchars($job['department']); ?></span>
                                        <h4 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white"><?php echo htmlspecialchars($job['title']); ?></h4>
                                    </div>
                                    <span class="px-3 py-1 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[10px] font-bold rounded-full flex items-center">
                                        <i aria-hidden="true" class="bi bi-geo-alt-fill text-amber-500 mr-1"></i> <?php echo htmlspecialchars($job['location']); ?>
                                    </span>
                                </div>
                                
                                <div class="space-y-4 text-xs text-slate-600 dark:text-slate-300">
                                    <div>
                                        <h5 class="font-bold text-slate-900 dark:text-white uppercase tracking-wider text-[10px] mb-1">Role Description</h5>
                                        <p class="leading-relaxed text-slate-500 dark:text-slate-400"><?php echo htmlspecialchars($job['description']); ?></p>
                                    </div>
                                    <div>
                                        <h5 class="font-bold text-slate-900 dark:text-white uppercase tracking-wider text-[10px] mb-1">Requirements & Qualifications</h5>
                                        <p class="leading-relaxed whitespace-pre-line text-slate-500 dark:text-slate-400"><?php echo htmlspecialchars($job['requirements']); ?></p>
                                    </div>
                                </div>
                            </div>

                            <button type="button" onclick="selectJobAndScroll(<?php echo $job['id']; ?>)" class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-2.5 bg-slate-900 dark:bg-slate-800 hover:bg-amber-500 dark:hover:bg-amber-500 hover:text-slate-950 text-white text-xs font-bold uppercase tracking-wider rounded-xl transition-all">
                                Apply for this position <i class="bi bi-arrow-right ml-2"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Application Form Sidebar -->
            <div id="apply-form-container" class="lg:sticky lg:top-24">
                <div class="bg-white dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 p-6 sm:p-8 rounded-3xl shadow-lg">
                    <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white mb-1">Submit Application</h3>
                    <p class="text-[10px] sm:text-xs text-slate-400 mb-6">Attach your CV and submit your candidacy for review by corporate HR.</p>

                    <form action="careers.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="apply_job" value="1">

                        <div>
                            <label for="app_job_id" class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1">Position Applied For</label>
                            <select id="app_job_id" name="job_id" required class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                                <option value="0">General Application</option>
                                <?php foreach ($jobs as $job): ?>
                                    <option value="<?php echo $job['id']; ?>"><?php echo htmlspecialchars($job['title']); ?> (<?php echo htmlspecialchars($job['department']); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="app_name" class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1">Full Name</label>
                            <input type="text" id="app_name" name="name" required placeholder="Chidi Nwachukwu" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                        </div>

                        <div>
                            <label for="app_email" class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Email Address *</label>
                            <input type="email" id="app_email" name="email" required placeholder="chidi.nwachukwu@sarkinmotahq.com" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                        </div>

                        <div>
                            <label for="app_phone" class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1">Phone Number</label>
                            <input type="text" id="app_phone" name="phone" required placeholder="+234 800 000 0000" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500">
                        </div>

                        <div>
                            <label for="app_cover" class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1">Cover Letter Summary</label>
                            <textarea id="app_cover" name="cover_letter" rows="3" placeholder="Briefly state your qualifications and relevant experience..." class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-amber-500"></textarea>
                        </div>

                        <div>
                            <label for="app_resume" class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1">Resume / CV (PDF, DOC, DOCX - Max 10MB)</label>
                            <input type="file" id="app_resume" name="resume" required accept=".pdf,.doc,.docx" class="w-full text-xs text-slate-500 dark:text-slate-400 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-[10px] file:font-bold file:bg-amber-500 file:text-slate-950 hover:file:bg-amber-600 transition-all">
                        </div>

                        <button type="submit" class="w-full bg-amber-500 text-slate-950 font-bold uppercase tracking-wider text-xs py-3.5 rounded-xl hover:bg-amber-600 transition-colors shadow-md mt-4">
                            Submit Application
                        </button>
                    </form>
                </div>
            </div>

        </div>

    </div>
</section>

<script>
function selectJobAndScroll(jobId) {
    const select = document.getElementById('app_job_id');
    if (select) {
        select.value = jobId;
    }
    const container = document.getElementById('apply-form-container');
    if (container) {
        container.scrollIntoView({ behavior: 'smooth' });
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
