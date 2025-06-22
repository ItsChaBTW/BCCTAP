<footer class="bg-gradient-to-r from-green-500 to-green-700 text-white py-8">
    <div class="container mx-auto px-4">
        <div class="flex flex-col md:flex-row justify-between items-center">
            <div class="mb-6 md:mb-0">
                <div class="flex items-center mb-3">
                    <div class="bg-green-100 rounded-full p-1 mr-2 shadow-md">
                        <span class="text-green-700 font-bold text-sm">BCC</span>
                    </div>
                    <h2 class="font-bold text-lg">BCCTAP</h2>
                </div>
                <p class="text-green-100 text-sm">&copy; <?php echo date('Y'); ?> Bago City College</p>
                <p class="text-green-100 text-sm">Time Attendance Platform</p>
            </div>
            
            <div class="grid grid-cols-2 gap-8 text-sm">
                <div>
                    <h3 class="font-semibold text-green-200 mb-3">Quick Links</h3>
                    <ul class="space-y-2 text-green-100">
                        <li><a href="<?php echo BASE_URL; ?>" class="hover:text-green-300 transition-colors">Home</a></li>
                        <li><a href="<?php echo BASE_URL; ?>student/login.php" class="hover:text-green-300 transition-colors">Student Portal</a></li>
                        <li><a href="<?php echo BASE_URL; ?>staff_login.php" class="hover:text-green-300 transition-colors">Staff Portal</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="font-semibold text-green-200 mb-3">Help & Support</h3>
                    <ul class="space-y-2 text-green-100">
                        <li><a href="#" class="hover:text-green-300 transition-colors">Contact Admin</a></li>
                        <li><a href="#" class="hover:text-green-300 transition-colors">Privacy Policy</a></li>
                        <li><a href="#" class="hover:text-green-300 transition-colors">Terms of Service</a></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="mt-8 pt-6 border-t border-green-800 text-center text-sm text-green-100">
            <p>Designed and developed for Bago City College</p>
        </div>
    </div>
</footer> 