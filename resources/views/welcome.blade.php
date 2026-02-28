<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>iWebGarage – Garage Management System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- If you’re using Vite / Breeze --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-gray-50 text-gray-800">

    {{-- Top navbar --}}
    <header class="w-full bg-white shadow-sm">
        <div class="max-w-6xl mx-auto px-4 lg:px-0">
            <div class="flex items-center justify-between h-16">
                {{-- Logo / brand --}}
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-indigo-500 to-emerald-400 flex items-center justify-center text-white font-bold text-lg">
                        G
                    </div>
                    <div class="leading-tight">
                        <span class="block font-semibold tracking-tight">
                            iWeb<span class="text-indigo-600">Garage</span>
                        </span>
                        <span class="block text-xs text-gray-500 uppercase tracking-wider">
                            Garage Management System
                        </span>
                    </div>
                </div>

                {{-- Nav links --}}
                <nav class="hidden md:flex items-center space-x-8 text-sm font-medium">
                    <a href="#pricing" class="text-gray-700 hover:text-indigo-600">Pricing</a>
                    <a href="#features" class="text-gray-700 hover:text-indigo-600">Features</a>
                    <a href="#faq" class="text-gray-700 hover:text-indigo-600">FAQs</a>
                    <a href="#contact" class="text-gray-700 hover:text-indigo-600">Contact Us</a>
                </nav>

                {{-- CTAs --}}
                <div class="hidden md:flex items-center space-x-3">
                    <a href="#contact"
                       class="px-4 py-2 rounded-md text-sm font-semibold bg-indigo-500 hover:bg-indigo-600 text-white shadow-sm">
                        Inquire Now
                    </a>
                    <a href="{{ route('login') }}"
                       class="px-4 py-2 rounded-md text-sm font-semibold border border-emerald-500 text-emerald-600 hover:bg-emerald-50">
                        Log In
                    </a>
                </div>

                {{-- Mobile menu button (non-functional placeholder) --}}
                <button class="md:hidden inline-flex items-center justify-center p-2 rounded-md text-gray-600 hover:bg-gray-100">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>
    </header>

    {{-- Hero + pricing --}}
    <section id="pricing" class="bg-gray-50">
        <div class="max-w-6xl mx-auto px-4 lg:px-0 py-12 lg:py-20 grid lg:grid-cols-2 gap-10 items-center">
            {{-- Text --}}
            {{-- Text --}}
            <div class="space-y-6">
                <p class="text-sm tracking-[0.25em] uppercase text-indigo-500 font-semibold">
                    GarageSuite (powered by iWebStudio)
                </p>

                <h1 class="text-3xl md:text-4xl font-bold tracking-tight text-gray-900">
                    Run Your Garage Smarter — From Job Card to Payment.
                </h1>

                <p class="text-lg text-gray-600 max-w-xl">
                    Manage jobs, customers, invoices, payments, and inventory in one simple system.
                    Built for Kenyan workshops that want to grow with less paperwork.
                </p>

                <div class="flex items-baseline space-x-2">
                    <span class="text-5xl font-bold text-gray-900">KES 5,000</span>
                    <span class="text-base font-semibold text-gray-500">/ month</span>
                </div>

                <p class="text-sm text-gray-600 max-w-md">
                    Unlimited Job Cards, Customer &amp; Vehicle Records, Invoices &amp; Payments, Inventory Tracking,
                    Reports, Multiple Staff Logins, and mobile-friendly access — all included.
                </p>

                <div class="flex flex-wrap items-center gap-3 pt-2">
                    <a href="{{ route('register') }}"
                    class="px-6 py-3 rounded-md text-sm font-semibold bg-indigo-500 hover:bg-indigo-600 text-white shadow">
                        Start Free Trial
                    </a>

                    <a href="#features"
                    class="px-6 py-3 rounded-md text-sm font-semibold border border-gray-300 text-gray-800 hover:bg-gray-100">
                        See How It Works
                    </a>

                    <a href="#contact"
                    class="px-6 py-3 rounded-md text-sm font-semibold text-indigo-600 hover:text-indigo-700">
                        Book a Demo
                    </a>
                </div>

                <div class="text-sm text-gray-500 pt-2">
                    ✔ Works on phone &amp; desktop &nbsp; • &nbsp;
                    ✔ Local support via WhatsApp &nbsp; • &nbsp;
                    ✔ Secure backups
                </div>

                {{-- Store / web login badges (placeholder buttons) --}}
                {{-- Quick access badges (real) --}}
                <div class="flex flex-wrap gap-3 pt-4">

                    {{-- Web Login --}}
                    <a href="{{ route('login') }}"
                    class="flex items-center space-x-3 px-4 py-2 rounded-md bg-gray-900 text-white text-xs uppercase tracking-wide hover:bg-black transition">
                        <div class="w-5 h-5 rounded-full bg-white flex items-center justify-center">
                            <span class="text-[10px] font-bold text-yellow-500">◎</span>
                        </div>
                        <div class="text-left leading-tight">
                            <span class="block text-[10px]">Use Chrome</span>
                            <span class="block text-xs font-semibold">Web Login</span>
                        </div>
                    </a>

                    {{-- WhatsApp Demo (optional but HIGH converting) --}}
                    <a href="https://wa.me/254706673337?text=Hi%20GarageSuite,%20I%20want%20a%20demo%20for%20my%20garage."
                    target="_blank" rel="noopener"
                    class="flex items-center space-x-3 px-4 py-2 rounded-md bg-emerald-600 text-white text-xs uppercase tracking-wide hover:bg-emerald-700 transition">
                        <div class="w-5 h-5 rounded bg-white/15 flex items-center justify-center text-[10px] font-bold">
                            WA
                        </div>
                        <div class="text-left leading-tight">
                            <span class="block text-[10px]">Chat on</span>
                            <span class="block text-xs font-semibold">WhatsApp</span>
                        </div>
                    </a>

                </div>

            </div>


            {{-- Illustration / hero image placeholder --}}
            <div class="flex justify-center">
                <div
                    class="w-full max-w-md h-72 rounded-3xl bg-white shadow-lg border border-gray-100 flex items-center justify-center">
                    <div class="space-y-4 px-8">
                        <div class="h-6 w-32 rounded-full bg-indigo-100"></div>
                        <div class="h-4 w-48 rounded-full bg-gray-100"></div>
                        <div class="grid grid-cols-3 gap-3 pt-4">
                            <div class="h-20 rounded-2xl bg-indigo-50"></div>
                            <div class="h-20 rounded-2xl bg-emerald-50"></div>
                            <div class="h-20 rounded-2xl bg-yellow-50"></div>
                        </div>
                        <div class="h-28 rounded-2xl bg-gray-50 border border-dashed border-gray-200"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Feature sections --}}
    <section id="features" class="bg-white border-t border-gray-100">
        <div class="max-w-6xl mx-auto px-4 lg:px-0 py-12 lg:py-16 space-y-16">

            {{-- Online appointment booking --}}
            <div class="grid lg:grid-cols-2 gap-10 items-center">
                <div class="order-2 lg:order-1">
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">
                        Online Appointment Booking
                    </h2>
                    <div class="h-0.5 w-16 bg-emerald-500 mb-4"></div>
                    <p class="text-gray-600 mb-4">
                        Stop juggling calls and missed messages. Let your customers book appointments online at any
                        time, even when the reception desk is busy or closed.
                    </p>
                    <p class="text-gray-600 mb-6">
                        Every booking flows straight into your calendar and job list so your team always knows
                        what's coming in next.
                    </p>
                    <a href="#contact"
                       class="inline-flex px-5 py-2.5 rounded-md text-sm font-semibold bg-indigo-500 hover:bg-indigo-600 text-white shadow">
                        Inquire Now
                    </a>
                </div>

                <div class="order-1 lg:order-2 flex justify-center">
                    <div class="w-full max-w-md h-64 rounded-3xl bg-indigo-50 flex items-center justify-center">
                        <div class="w-40 h-40 rounded-2xl bg-white shadow flex flex-col p-4 space-y-3">
                            <div class="h-3 w-16 rounded-full bg-indigo-100"></div>
                            <div class="flex-1 grid grid-cols-7 gap-1">
                                @for ($i = 0; $i < 28; $i++)
                                    <div class="h-5 rounded bg-gray-50"></div>
                                @endfor
                            </div>
                            <div class="h-3 w-24 rounded-full bg-emerald-100"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Pickup & drop tracking --}}
            <div class="grid lg:grid-cols-2 gap-10 items-center">
                <div class="flex justify-center">
                    <div class="w-full max-w-md h-64 rounded-3xl bg-emerald-50 flex items-center justify-center">
                        <div class="flex items-end space-x-4">
                            <div class="w-24 h-40 rounded-2xl bg-white shadow flex flex-col p-3 space-y-2">
                                <div class="h-3 w-16 bg-emerald-100 rounded"></div>
                                <div class="flex-1 rounded bg-gray-50"></div>
                                <div class="h-3 w-12 bg-gray-100 rounded"></div>
                            </div>
                            <div class="w-28 h-20 rounded-2xl bg-yellow-100 flex items-center justify-center text-xs font-semibold text-yellow-800">
                                Driver on the way
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">
                        Pickup and Drop Tracking
                    </h2>
                    <div class="h-0.5 w-16 bg-emerald-500 mb-4"></div>
                    <p class="text-gray-600 mb-4">
                        Track pickup and drop-off jobs in real time. See which vehicles are on the road, which
                        driver is assigned, and when the car is expected back at the workshop.
                    </p>
                    <p class="text-gray-600 mb-6">
                        Reduce back-and-forth calls and keep both your team and customers updated with clear statuses.
                    </p>
                    <a href="#contact"
                       class="inline-flex px-5 py-2.5 rounded-md text-sm font-semibold bg-indigo-500 hover:bg-indigo-600 text-white shadow">
                        Inquire Now
                    </a>
                </div>
            </div>

        </div>
    </section>

    {{-- FAQ (simple placeholder) --}}
    <section id="faq" class="bg-gray-50 border-t border-gray-100">
        <div class="max-w-6xl mx-auto px-4 lg:px-0 py-12 lg:py-16">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Frequently Asked Questions</h2>
            <div class="space-y-4 text-sm text-gray-700">
                <div>
                    <p class="font-semibold">Is there a limit on job cards or vehicles?</p>
                    <p class="text-gray-600">
                        No. The standard plan includes unlimited job cards, customers and vehicles for your garage.
                    </p>
                </div>
                <div>
                    <p class="font-semibold">Can multiple staff log in?</p>
                    <p class="text-gray-600">
                        Yes. Create separate logins for your service advisors, technicians and managers with proper
                        access control.
                    </p>
                </div>
                <div>
                    <p class="font-semibold">Do you support Kenyan garages &amp; M-Pesa?</p>
                    <p class="text-gray-600">
                        iWebGarage is designed with local garages in mind, including options to connect to mobile
                        payments and local workflows.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- Contact / inquiry form --}}
    <section id="contact" class="bg-white border-t border-gray-100">
        <div class="max-w-6xl mx-auto px-4 lg:px-0 py-12 lg:py-16">
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Get in touch</h2>
            <p class="text-gray-600 mb-6">
                Share a few details and we’ll contact you with a personalised demo or quote.
            </p>

            <div class="grid lg:grid-cols-3 gap-10">
                {{-- Form --}}
                <div class="lg:col-span-2">
                    <form class="space-y-4">
                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                                <input type="text" class="w-full rounded-md border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input type="email" class="w-full rounded-md border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                        </div>

                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                <input type="text" class="w-full rounded-md border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                                <input type="text" class="w-full rounded-md border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                            <textarea rows="4"
                                      class="w-full rounded-md border-gray-300 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                        </div>

                        <button type="submit"
                                class="inline-flex px-6 py-3 rounded-md text-sm font-semibold bg-indigo-500 hover:bg-indigo-600 text-white shadow">
                            Submit Now
                        </button>
                    </form>
                </div>

                {{-- Contact / footer mini columns --}}
                <div class="space-y-6 text-sm">
                    <div>
                        <h3 class="font-semibold text-gray-900 mb-1">iWebGarage</h3>
                        <p class="text-gray-600">
                            Smart garage management for workshops that want to grow with less paperwork.
                        </p>
                    </div>

                    <div>
                        <h4 class="font-semibold text-gray-900 mb-1">Contact Us</h4>
                        <p class="text-gray-600">
                            WhatsApp: +254 xxx xxx xxx<br>
                            Email: support@iwebgarage.com
                        </p>
                    </div>

                    <div>
                        <h4 class="font-semibold text-gray-900 mb-1">Follow</h4>
                        <p class="text-gray-600">
                            LinkedIn • Facebook • Instagram
                        </p>
                    </div>
                </div>
            </div>

            <p class="mt-10 text-xs text-gray-500 text-center">
                © {{ date('Y') }} iWebGarage. All rights reserved.
            </p>
        </div>
    </section>

</body>
</html>
