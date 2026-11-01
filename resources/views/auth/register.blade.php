<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create account · Pulse</title>
    <!-- Tailwind via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts: Inter + Space Grotesk -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300..700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-display { font-family: 'Space Grotesk', sans-serif; }
        .gold-text {
            background: linear-gradient(145deg, #BF953F, #FCF6B5, #B38728, #FBF5B7, #AA771C);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .gold-ring {
            transition: box-shadow 0.25s ease, transform 0.2s ease;
        }
        .gold-ring:hover {
            box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.35);
        }
        input:focus {
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15);
        }
        [x-cloak] { display: none !important; }
        /* Password strength bar transition */
        .strength-bar { transition: width 0.3s ease, background-color 0.3s ease; }
    </style>
</head>
<body class="bg-[#FDFBF7] text-gray-800 antialiased overflow-x-hidden min-h-screen flex flex-col">

    <!-- Background decorative blobs (golden) -->
    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
        <div class="absolute top-[-10%] left-[-10%] w-[500px] h-[500px] rounded-full bg-amber-200 blur-[120px] opacity-50"></div>
        <div class="absolute bottom-[-5%] right-[-5%] w-[600px] h-[600px] rounded-full bg-yellow-200 blur-[130px] opacity-40"></div>
        <div class="absolute top-[40%] right-[10%] w-[350px] h-[350px] rounded-full bg-orange-100 blur-[100px] opacity-30"></div>
        <div class="absolute top-[20%] left-[30%] w-[250px] h-[250px] rounded-full bg-yellow-300 blur-[90px] opacity-20"></div>
    </div>

 
    <!-- MAIN AUTH SECTION -->
    <main class="flex-1 flex items-center justify-center px-4 py-12 relative z-10">
        <div class="w-full max-w-md">
            <!-- Card with golden glow -->
            <div class="relative">
                <div class="absolute inset-0 bg-gradient-to-tr from-amber-300 via-yellow-200 to-orange-300 rounded-[36px] blur-[40px] opacity-30 -z-10"></div>

                <div class="bg-white/90 backdrop-blur-xl rounded-[32px] border border-amber-200/70 shadow-xl shadow-amber-100/50 p-8 sm:p-10">

                    <!-- Heading -->
                    <div class="mb-8 text-center">
                        <h1 class="font-display text-3xl font-bold text-gray-900">
                            Join the <span class="gold-text">pulse</span>
                        </h1>
                        <p class="mt-2 text-sm text-gray-500">Create your account. It only takes a minute.</p>
                    </div>

                    <!-- Form -->
                    <form method="POST" action="{{ route('register') }}" class="space-y-5">
                        @csrf

                        <!-- Name -->
                        <div>
                            <label for="name" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-amber-700/80">Full name</label>
                            <div class="relative">
                                <i class="fa-regular fa-user pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-amber-500/70 text-sm"></i>
                                <input
                                    id="name" type="text" name="name" value="{{ old('name') }}"
                                    required autofocus autocomplete="name"
                                    placeholder="Jane Doe"
                                    class="w-full rounded-xl border border-amber-200/80 bg-white/80 py-3 pl-11 pr-4 text-sm text-gray-800 placeholder-gray-400 outline-none transition focus:border-amber-400 focus:ring-2 focus:ring-amber-300/30"
                                >
                            </div>
                            @error('name')
                                <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="email" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-amber-700/80">Email</label>
                            <div class="relative">
                                <i class="fa-regular fa-envelope pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-amber-500/70 text-sm"></i>
                                <input
                                    id="email" type="email" name="email" value="{{ old('email') }}"
                                    required autocomplete="username"
                                    placeholder="you@example.com"
                                    class="w-full rounded-xl border border-amber-200/80 bg-white/80 py-3 pl-11 pr-4 text-sm text-gray-800 placeholder-gray-400 outline-none transition focus:border-amber-400 focus:ring-2 focus:ring-amber-300/30"
                                >
                            </div>
                            @error('email')
                                <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Password with strength indicator -->
                        <div x-data="{
                            show: false,
                            password: '',
                            get strength() {
                                let s = 0;
                                if (this.password.length >= 8) s++;
                                if (/[A-Z]/.test(this.password)) s++;
                                if (/[0-9]/.test(this.password)) s++;
                                if (/[^A-Za-z0-9]/.test(this.password)) s++;
                                return s;
                            },
                            get strengthLabel() {
                                return ['Too weak', 'Weak', 'Fair', 'Good', 'Strong'][this.strength];
                            },
                            get strengthColor() {
                                return ['bg-red-400', 'bg-orange-400', 'bg-yellow-400', 'bg-amber-500', 'bg-emerald-500'][this.strength];
                            },
                            get strengthText() {
                                return ['text-red-500', 'text-orange-500', 'text-yellow-600', 'text-amber-600', 'text-emerald-600'][this.strength];
                            }
                        }">
                            <div class="mb-1.5 flex items-center justify-between">
                                <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-amber-700/80">Password</label>
                                <span class="text-[10px] font-semibold" x-show="password.length > 0" x-cloak :class="strengthText" x-text="strengthLabel"></span>
                            </div>
                            <div class="relative">
                                <i class="fa-solid fa-lock pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-amber-500/70 text-sm"></i>
                                <input
                                    id="password" :type="show ? 'text' : 'password'" name="password"
                                    x-model="password"
                                    required autocomplete="new-password"
                                    placeholder="••••••••"
                                    class="w-full rounded-xl border border-amber-200/80 bg-white/80 py-3 pl-11 pr-11 text-sm text-gray-800 placeholder-gray-400 outline-none transition focus:border-amber-400 focus:ring-2 focus:ring-amber-300/30"
                                >
                                <button type="button" @click="show = !show"
                                        class="absolute right-3.5 top-1/2 -translate-y-1/2 text-amber-400 hover:text-amber-600 transition">
                                    <i x-show="!show" class="fa-regular fa-eye text-sm"></i>
                                    <i x-show="show" x-cloak class="fa-regular fa-eye-slash text-sm"></i>
                                </button>
                            </div>
                            <!-- Strength bar -->
                            <div class="mt-2 flex gap-1" x-show="password.length > 0" x-cloak>
                                <template x-for="i in 4" :key="i">
                                    <div class="h-1 flex-1 rounded-full bg-amber-100 overflow-hidden">
                                        <div class="h-full strength-bar rounded-full"
                                             :class="i <= strength ? strengthColor : 'bg-transparent'"
                                             :style="`width: ${i <= strength ? '100%' : '0%'}`"></div>
                                    </div>
                                </template>
                            </div>
                            <p class="mt-1.5 text-[11px] text-gray-400" x-show="password.length === 0" x-cloak>
                                Use 8+ characters with a mix of letters, numbers & symbols.
                            </p>
                            @error('password')
                                <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Confirm Password -->
                        <div x-data="{ show: false }">
                            <label for="password_confirmation" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-amber-700/80">Confirm password</label>
                            <div class="relative">
                                <i class="fa-solid fa-lock pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-amber-500/70 text-sm"></i>
                                <input
                                    id="password_confirmation" :type="show ? 'text' : 'password'" name="password_confirmation"
                                    required autocomplete="new-password"
                                    placeholder="••••••••"
                                    class="w-full rounded-xl border border-amber-200/80 bg-white/80 py-3 pl-11 pr-11 text-sm text-gray-800 placeholder-gray-400 outline-none transition focus:border-amber-400 focus:ring-2 focus:ring-amber-300/30"
                                >
                                <button type="button" @click="show = !show"
                                        class="absolute right-3.5 top-1/2 -translate-y-1/2 text-amber-400 hover:text-amber-600 transition">
                                    <i x-show="!show" class="fa-regular fa-eye text-sm"></i>
                                    <i x-show="show" x-cloak class="fa-regular fa-eye-slash text-sm"></i>
                                </button>
                            </div>
                            @error('password_confirmation')
                                <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Terms checkbox -->
                        <label class="flex items-start gap-2.5 text-sm text-gray-600 cursor-pointer">
                            <input type="checkbox" name="terms" required
                                   class="mt-0.5 h-4 w-4 rounded border-amber-300 bg-white text-amber-500 focus:ring-amber-400/40 accent-amber-500">
                            <span>
                                I agree to the
                                <a href="#" class="font-semibold text-amber-600 hover:text-amber-700 transition">Terms of Service</a>
                                and
                                <a href="#" class="font-semibold text-amber-600 hover:text-amber-700 transition">Privacy Policy</a>.
                            </span>
                        </label>
                        @error('terms')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror

                        <!-- Submit -->
                        <button type="submit"
                                class="w-full rounded-xl bg-gradient-to-r from-amber-500 via-yellow-400 to-amber-600 py-3 text-sm font-bold text-white shadow-lg shadow-amber-200/70 transition hover:shadow-amber-300/80 hover:scale-[1.01] active:scale-[.99] gold-ring">
                            Create account
                            <i class="fa-solid fa-arrow-right ml-1.5 text-xs"></i>
                        </button>
                    </form>

                    <!-- Divider -->
                    <div class="my-6 flex items-center gap-3">
                        <div class="flex-1 h-px bg-amber-100"></div>
                        <span class="text-xs font-medium text-gray-400 uppercase tracking-wider">or sign up with</span>
                        <div class="flex-1 h-px bg-amber-100"></div>
                    </div>

                    <!-- Social signup buttons -->
                    <div class="grid grid-cols-3 gap-3">
                        <button type="button" class="flex items-center justify-center gap-2 rounded-xl border border-amber-200/80 bg-white/80 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-amber-50 hover:border-amber-300 transition">
                            <i class="fa-brands fa-google text-amber-600"></i>
                            <span class="hidden sm:inline">Google</span>
                        </button>
                        <button type="button" class="flex items-center justify-center gap-2 rounded-xl border border-amber-200/80 bg-white/80 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-amber-50 hover:border-amber-300 transition">
                            <i class="fa-brands fa-apple text-gray-800"></i>
                            <span class="hidden sm:inline">Apple</span>
                        </button>
                        <button type="button" class="flex items-center justify-center gap-2 rounded-xl border border-amber-200/80 bg-white/80 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-amber-50 hover:border-amber-300 transition">
                            <i class="fa-brands fa-discord text-indigo-500"></i>
                            <span class="hidden sm:inline">Discord</span>
                        </button>
                    </div>

                    <!-- Footer link -->
                    <p class="mt-8 text-center text-sm text-gray-500">
                        Already have an account?
                        <a href="{{ route('login') }}" class="font-semibold text-amber-600 hover:text-amber-700 transition">Sign in</a>
                    </p>
                </div>
            </div>

            <!-- Trust note -->
            <p class="mt-6 text-center text-xs text-gray-400">
                <i class="fa-solid fa-shield-halved text-amber-400 mr-1"></i>
                Protected by end‑to‑end encryption. No spam, ever.
            </p>
        </div>
    </main>
 
</body>
</html>