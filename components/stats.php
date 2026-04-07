<section id="stats" class="bg-gradient-to-br from-blue-50 to-cyan-50 py-16 relative overflow-hidden">
    <!-- Enhanced Background Elements -->
    <div class="absolute inset-0">
        <div class="absolute top-10 left-1/4 w-32 h-32 bg-gradient-to-br from-blue-400/8 to-transparent rounded-full blur-xl animate-pulse"></div>
        <div class="absolute bottom-10 right-1/4 w-40 h-40 bg-gradient-to-br from-cyan-400/8 to-transparent rounded-full blur-xl animate-bounce"></div>
        <div class="absolute top-1/2 left-1/3 w-24 h-24 bg-gradient-to-br from-purple-400/6 to-transparent rounded-full blur-lg animate-spin"></div>
    </div>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <!-- Enhanced Section Header -->
        <div class="text-center mb-16">
            <h2 class="text-4xl font-bold text-gray-900 mb-4">Trusted by <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-cyan-600">Industry Leaders</span></h2>
            <p class="text-lg text-gray-600 max-w-3xl mx-auto">
                Join thousands of businesses that have transformed their hiring with Apex Nexus
            </p>
        </div>

        <!-- Professional Stats Grid with Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-12">
            <div class="group">
                <div class="bg-white rounded-xl p-6 shadow-md border border-gray-100 hover:shadow-xl hover:border-blue-200 transition-all duration-300 hover:-translate-y-1 text-center">
                    <div class="stat-number text-4xl font-bold text-blue-600 mb-2 transition-all duration-300" data-target="10000">0</div>
                    <div class="text-gray-600 text-sm font-medium">Jobs Posted</div>
                </div>
            </div>
            
            <div class="group">
                <div class="bg-white rounded-xl p-6 shadow-md border border-gray-100 hover:shadow-xl hover:border-green-200 transition-all duration-300 hover:-translate-y-1 text-center">
                    <div class="stat-number text-4xl font-bold text-green-600 mb-2 transition-all duration-300" data-target="2500">0</div>
                    <div class="text-gray-600 text-sm font-medium">Companies</div>
                </div>
            </div>
            
            <div class="group">
                <div class="bg-white rounded-xl p-6 shadow-md border border-gray-100 hover:shadow-xl hover:border-purple-200 transition-all duration-300 hover:-translate-y-1 text-center">
                    <div class="stat-number text-4xl font-bold text-purple-600 mb-2 transition-all duration-300" data-target="50000">0</div>
                    <div class="text-gray-600 text-sm font-medium">Candidates</div>
                </div>
            </div>
            
            <div class="group">
                <div class="bg-white rounded-xl p-6 shadow-md border border-gray-100 hover:shadow-xl hover:border-orange-200 transition-all duration-300 hover:-translate-y-1 text-center">
                    <div class="stat-number text-4xl font-bold text-orange-600 mb-2 transition-all duration-300" data-target="98">0</div>
                    <div class="text-gray-600 text-sm font-medium">Success Rate %</div>
                </div>
            </div>
        </div>

        <!-- Additional Stats with Enhanced Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="group">
                <div class="bg-white rounded-xl p-5 shadow-md border border-gray-100 hover:shadow-lg hover:border-cyan-200 transition-all duration-300 hover:-translate-y-1 text-center">
                    <div class="stat-number text-3xl font-bold text-cyan-600 mb-2 transition-all duration-300" data-target="15000">0</div>
                    <div class="text-gray-600 text-sm font-medium">Active Users</div>
                </div>
            </div>
            
            <div class="group">
                <div class="bg-white rounded-xl p-5 shadow-md border border-gray-100 hover:shadow-lg hover:border-indigo-200 transition-all duration-300 hover:-translate-y-1 text-center">
                    <div class="stat-number text-3xl font-bold text-indigo-600 mb-2 transition-all duration-300" data-target="5000">0</div>
                    <div class="text-gray-600 text-sm font-medium">Daily Applications</div>
                </div>
            </div>
            
            <div class="group">
                <div class="bg-white rounded-xl p-5 shadow-md border border-gray-100 hover:shadow-lg hover:border-teal-200 transition-all duration-300 hover:-translate-y-1 text-center">
                    <div class="stat-number text-3xl font-bold text-teal-600 mb-2 transition-all duration-300" data-target="24">0</div>
                    <div class="text-gray-600 text-sm font-medium">Hour Response</div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .stat-number {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .group:hover .stat-number {
            transform: scale(1.05);
        }
    </style>

    <script>
        // Enhanced Counter Animation
        function animateCounter(element, target, duration = 2000) {
            let start = 0;
            const increment = target / (duration / 16);
            const timer = setInterval(() => {
                start += increment;
                if (start >= target) {
                    start = target;
                    clearInterval(timer);
                }
                element.textContent = Math.floor(start).toLocaleString();
            }, 16);
        }

        // Initialize counters with enhanced effects
        document.addEventListener('DOMContentLoaded', function() {
            const counters = document.querySelectorAll('.stat-number');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const counter = entry.target;
                        const target = parseInt(counter.getAttribute('data-target'));
                        animateCounter(counter, target);
                        observer.unobserve(counter);
                    }
                });
            }, { threshold: 0.5 });

            counters.forEach(counter => observer.observe(counter));
        });
    </script>
</section>
