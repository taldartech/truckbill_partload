<template>
    <div class="app-wrapper bg-light">
        <Navbar />
        <Sidebar />
        <main class="app-main py-4">
            <div class="app-content-header mb-4">
                <div class="container-fluid px-4">
                    <div class="row align-items-center">
                        <div class="col-sm-6">
                            <h4 class="mb-0 fw-bold text-dark">{{ title }}</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="app-content">
                <div class="container-fluid px-4">
                    <slot />
                </div>
            </div>
        </main>
        <Footer />
    </div>
</template>

<script setup>
import Navbar from '@/Components/Navbar.vue';
import Sidebar from '@/Components/Sidebar.vue';
import Footer from '@/Components/Footer.vue';

defineProps({
    title: {
        type: String,
        default: 'Dashboard'
    }
});
</script>

<style>
.app-wrapper {
    height: 100vh;
    overflow: hidden;
}

.app-main {
    transition: margin-left 0.3s;
    overflow-y: auto;
    overflow-x: hidden;
}
</style>
