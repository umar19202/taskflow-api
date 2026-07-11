<template>
    <div class="min-h-screen flex flex-col bg-white">
        <header class="sticky top-0 z-50 bg-white/90 backdrop-blur-sm border-b border-gray-100">
            <div class="max-w-6xl mx-auto px-6 flex items-center justify-between h-16">
                <div class="flex items-center gap-3 font-bold text-lg text-gray-900">
                    <img src="/images/logo-icon.svg" alt="" width="26" />
                    <span>TaskFlow</span>
                </div>
                <div class="flex items-center gap-3">
                    <router-link to="/login" class="text-sm font-medium text-gray-600 hover:text-gray-900 px-4 py-2">Sign In</router-link>
                    <router-link to="/register" class="btn btn-primary btn-sm">Get Started</router-link>
                </div>
            </div>
        </header>

        <section class="flex-1 flex items-center justify-center text-center px-6 py-24">
            <div class="max-w-2xl">
                <h1 class="text-4xl md:text-5xl font-extrabold text-gray-900 leading-tight mb-4">
                    Task Management<br>
                    <span class="text-primary">Made Simple</span>
                </h1>
                <p class="text-lg text-gray-500 mb-8 max-w-lg mx-auto">
                    Organize projects, track tasks, collaborate with your team, and get things done.
                </p>
                <div class="flex gap-4 justify-center">
                    <router-link to="/register" class="btn btn-primary">Get Started Free</router-link>
                    <router-link to="/login" class="btn btn-outline-secondary">Sign In</router-link>
                </div>
            </div>
        </section>

        <footer class="border-t border-gray-100 py-6 text-center text-sm text-gray-400">
            &copy; {{ new Date().getFullYear() }} TaskFlow. All rights reserved.
        </footer>
    </div>
</template>
