<section id="stats" class="bg-blue-900 py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Section Heading -->
        <div class="text-center mb-16">
            <h2 class="text-4xl font-bold text-white mb-4">Trusted by Leading Companies</h2>
            <p class="text-xl text-blue-300 max-w-3xl mx-auto">
                Join thousands of businesses that have transformed their hiring with Apex Nexus
            </p>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
            <!-- Jobs Posted -->
            <div class="text-center">
                <div class="stat-number text-4xl font-black text-white mb-2" data-target="10000">0</div>
                <div class="text-blue-300 text-sm uppercase tracking-wide">Jobs Posted</div>
            </div>

            <!-- Companies -->
            <div class="text-center">
                <div class="stat-number text-4xl font-black text-white mb-2" data-target="2500">0</div>
                <div class="text-blue-300 text-sm uppercase tracking-wide">Companies</div>
            </div>

            <!-- Candidates -->
            <div class="text-center">
                <div class="stat-number text-4xl font-black text-white mb-2" data-target="50000">0</div>
                <div class="text-blue-300 text-sm uppercase tracking-wide">Candidates</div>
            </div>

            <!-- Satisfaction Rate -->
            <div class="text-center">
                <div class="stat-number text-4xl font-black text-white mb-2" data-target="98">0</div>
                <div class="text-blue-300 text-sm uppercase tracking-wide">Satisfaction Rate</div>
            </div>
        </div>

        <!-- Additional Trust Indicators -->
        <div class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="text-center">
                <div class="flex justify-center mb-4">
                    <svg class="h-12 w-12 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <h4 class="text-white font-semibold mb-2">24/7 Support</h4>
                <p class="text-blue-300 text-sm">Round-the-clock assistance for all your recruitment needs</p>
            </div>

            <div class="text-center">
                <div class="flex justify-center mb-4">
                    <svg class="h-12 w-12 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <h4 class="text-white font-semibold mb-2">Secure Platform</h4>
                <p class="text-blue-300 text-sm">Bank-level security for your data and candidate information</p>
            </div>

            <div class="text-center">
                <div class="flex justify-center mb-4">
                    <svg class="h-12 w-12 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"></path>
                    </svg>
                </div>
                <h4 class="text-white font-semibold mb-2">Expert Team</h4>
                <p class="text-blue-300 text-sm">Dedicated recruitment specialists to help you succeed</p>
            </div>
        </div>
    </div>

    <script>
        // Count up animation for stats
        const observerOptions = {
            threshold: 0.5,
            rootMargin: '0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const statNumbers = entry.target.querySelectorAll('.stat-number');
                    statNumbers.forEach(stat => {
                        const target = parseInt(stat.getAttribute('data-target'));
                        const duration = 2000; // 2 seconds
                        const increment = target / (duration / 16); // 60fps
                        let current = 0;

                        const updateCounter = () => {
                            current += increment;
                            if (current < target) {
                                stat.textContent = Math.ceil(current).toLocaleString();
                                requestAnimationFrame(updateCounter);
                            } else {
                                stat.textContent = target.toLocaleString();
                                if (stat.getAttribute('data-target') === '98') {
                                    stat.textContent = target + '%';
                                }
                            }
                        };

                        updateCounter();
                    });
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        // Start observing the stats section
        const statsSection = document.querySelector('#stats');
        if (statsSection) {
            observer.observe(statsSection);
        }
    </script>
</section>
