<template>
    <footer class="app-footer mt-2 bg-white border-top py-2 px-2 px-md-3">
        <div class="container-fluid p-0">
            <div class="truckbill-footer-content d-flex flex-column flex-md-row align-items-center justify-content-between w-100 text-center text-md-start gap-1 gap-md-2">
                <!-- Branding: TruckBill – A Product of Taldar Tech Consultancy -->
                <div class="footer-col-brand order-2 order-md-1 d-inline-flex align-items-center justify-content-center justify-content-md-start text-nowrap">
                    <strong class="text-dark">TruckBill</strong>
                    <span class="text-muted mx-1 mx-md-2">—</span>
                    <span class="d-inline-flex align-items-center text-secondary">
                        A Product of
                        <a
                            href="https://taldartechconsultancy.com"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-success fw-bold text-decoration-none ms-1"
                        >
                            Taldar Tech Consultancy
                        </a>
                    </span>
                </div>

                <!-- Footer Links: Help & Support, Privacy Policy, Terms & Conditions, Contact Us -->
                <nav class="footer-col-nav order-1 order-md-2 d-inline-flex align-items-center justify-content-center text-nowrap" aria-label="Footer Navigation">
                    <a
                        href="#helpSupportModal"
                        class="footer-link text-decoration-none"
                        role="button"
                        @click.prevent="isHelpModalOpen = true"
                    >
                        Help &amp; Support
                    </a>
                    <span class="text-muted mx-1 mx-md-2">•</span>
                    <a
                        href="https://www.truckbill.in/policies/privacy-policy.html"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="footer-link text-decoration-none"
                    >
                        Privacy Policy
                    </a>
                    <span class="text-muted mx-1 mx-md-2">•</span>
                    <a
                        href="https://www.truckbill.in/policies/terms-and-conditions.html"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="footer-link text-decoration-none"
                    >
                        Terms &amp; Conditions
                    </a>
                    <span class="text-muted mx-1 mx-md-2">•</span>
                    <a
                        href="https://www.truckbill.in/#contact"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="footer-link text-decoration-none"
                    >
                        Contact Us
                    </a>
                </nav>

                <!-- Copyright & Dynamic Application Version -->
                <div class="footer-col-meta order-3 order-md-3 d-inline-flex align-items-center justify-content-center justify-content-md-end text-nowrap gap-2 text-secondary">
                    <span>&copy; {{ currentYear }} TruckBill. All rights reserved.</span>
                    <span class="badge bg-success rounded-pill fw-semibold text-white">{{ displayVersion }}</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Help & Support Modal -->
    <Teleport to="body">
        <div
            v-if="isHelpModalOpen"
            class="modal fade show d-block"
            tabindex="-1"
            role="dialog"
            aria-modal="true"
            aria-labelledby="helpSupportModalLabel"
            style="background: rgba(15, 23, 42, 0.45); backdrop-filter: blur(4px);"
            @click.self="isHelpModalOpen = false"
        >
            <div class="modal-dialog modal-dialog-centered" style="max-width: 520px;">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <!-- Header with gradient and headphone icon -->
                    <div class="modal-header border-0 d-flex justify-content-between align-items-start p-4 pb-2" style="background: linear-gradient(180deg, #ecfdf5 0%, #f0fdf4 65%, #ffffff 100%);">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-white border d-flex align-items-center justify-content-center flex-shrink-0 shadow-sm" style="width: 44px; height: 44px; border-color: #d1fae5 !important;">
                                <!-- Green Headphone/Headset SVG identical to reference image -->
                                <svg width="24" height="24" viewBox="0 0 16 16" fill="#16a34a" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M8 1a5 5 0 0 0-5 5v1h1a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V6a6 6 0 1 1 12 0v6a2.5 2.5 0 0 1-2.5 2.5H9.366a1 1 0 0 1-.866.5h-1a1 1 0 1 1 0-2h1a1 1 0 0 1 .866.5H11.5A1.5 1.5 0 0 0 13 12h-1a1 1 0 0 1-1-1V8a1 1 0 0 1 1-1h1V6a5 5 0 0 0-5-5"/>
                                </svg>
                            </div>
                            <div>
                                <h5 id="helpSupportModalLabel" class="modal-title h5 fw-bold text-dark mb-0">Help &amp; Support</h5>
                                <div class="text-muted small mt-1" style="font-size: 0.8rem; color: #6b7280;">
                                    TruckBill Technical &amp; Operational Assistance
                                </div>
                            </div>
                        </div>
                        <button
                            type="button"
                            class="btn-close"
                            aria-label="Close"
                            @click="isHelpModalOpen = false"
                        ></button>
                    </div>

                    <!-- Body -->
                    <div class="modal-body px-4 py-3">
                        <p class="text-secondary small mb-3">
                            Need assistance with billing, lorry receipts (LR), GST compliance, or fleet operations? Contact our support team:
                        </p>

                        <!-- Email Support Card -->
                        <div
                            class="d-flex align-items-center justify-content-between p-3 rounded-3 mb-3 border"
                            style="background-color: #f0fdf4; border-color: #bbf7d0 !important; cursor: pointer;"
                            @click="handleEmailCardClick"
                            title="Click to compose email (Gmail/Webmail)"
                        >
                            <a
                                href="https://mail.google.com/mail/?view=cm&fs=1&to=support@truckbill.in"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="d-flex align-items-center gap-3 text-decoration-none min-w-0 flex-grow-1"
                            >
                                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 text-white" style="width: 40px; height: 40px; background-color: #16a34a;">
                                    <i class="fa-solid fa-envelope"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-uppercase fw-bold text-secondary" style="font-size: 0.6875rem; letter-spacing: 0.5px; line-height: 1.2;">EMAIL SUPPORT</div>
                                    <div class="fw-bold text-truncate" style="font-size: 0.95rem; color: #047857;">
                                        support@truckbill.in
                                    </div>
                                </div>
                            </a>
                            <button
                                type="button"
                                class="btn btn-sm bg-white d-inline-flex align-items-center gap-1 fw-semibold flex-shrink-0 shadow-sm"
                                style="border: 1px solid #16a34a; color: #047857; border-radius: 8px; padding: 5px 12px; font-size: 0.8125rem;"
                                @click.stop="copyText('support@truckbill.in', 'email')"
                            >
                                <svg v-if="copiedKey !== 'email'" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                </svg>
                                <svg v-else width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                                <span>{{ copiedKey === 'email' ? 'Copied' : 'Copy' }}</span>
                            </button>
                        </div>

                        <!-- Helpline & Contact Card -->
                        <div
                            class="d-flex align-items-center justify-content-between p-3 rounded-3 mb-3 border"
                            style="background-color: #eff6ff; border-color: #bfdbfe !important;"
                        >
                            <div class="d-flex align-items-center gap-3 min-w-0 flex-grow-1">
                                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 text-white" style="width: 40px; height: 40px; background-color: #2563eb;">
                                    <i class="fa-solid fa-phone"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-uppercase fw-bold text-secondary" style="font-size: 0.6875rem; letter-spacing: 0.5px; line-height: 1.2;">HELPLINE &amp; CONTACT</div>
                                    <div class="fw-bold text-truncate" style="font-size: 0.95rem; color: #2563eb;">
                                        <a href="tel:+919784451256" class="text-decoration-none" style="color: #2563eb;">+91 97844 51256</a>
                                        <span class="text-muted fw-normal mx-1">•</span>
                                        <a href="tel:+919784946004" class="text-decoration-none" style="color: #2563eb;">+91 97849 46004</a>
                                    </div>
                                </div>
                            </div>
                            <button
                                type="button"
                                class="btn btn-sm bg-white d-inline-flex align-items-center gap-1 fw-semibold flex-shrink-0 shadow-sm"
                                style="border: 1px solid #2563eb; color: #2563eb; border-radius: 8px; padding: 5px 12px; font-size: 0.8125rem;"
                                @click.stop="copyText('+91 97844 51256, +91 97849 46004', 'phone')"
                            >
                                <svg v-if="copiedKey !== 'phone'" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                </svg>
                                <svg v-else width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                                <span>{{ copiedKey === 'phone' ? 'Copied' : 'Copy' }}</span>
                            </button>
                        </div>

                        <!-- Details Card -->
                        <div class="p-3 rounded-3 bg-white border mb-2">
                            <div class="d-flex justify-content-between align-items-center small py-1">
                                <span class="text-secondary d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-clock text-warning"></i>
                                    Working Hours:
                                </span>
                                <span class="fw-bold text-dark">Mon – Sat: 10:00 AM – 7:00 PM IST</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center small pt-2 mt-1 border-top">
                                <span class="text-secondary d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-building text-secondary"></i>
                                    Product by:
                                </span>
                                <a
                                    href="https://taldartechconsultancy.com"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="text-success fw-bold text-decoration-none d-flex align-items-center gap-1"
                                >
                                    Taldar Tech Consultancy
                                    <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 0.72rem;"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="modal-footer border-top pt-3 px-4 pb-4">
                        <button
                            type="button"
                            class="btn btn-light w-100 fw-bold border"
                            style="border-radius: 8px; padding: 8px 24px; color: #16a34a !important; background-color: #e9ecef; border-color: #dee2e6; font-size: 0.9rem;"
                            @click="isHelpModalOpen = false"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    version: {
        type: String,
        default: 'v1.0.0'
    },
    year: {
        type: [String, Number],
        default: ''
    }
});

const page = usePage();
const displayVersion = computed(() => props.version || page.props?.app_version || 'v1.0.0');
const currentYear = computed(() => props.year || new Date().getFullYear());

const isHelpModalOpen = ref(false);
const copiedKey = ref('');

const copyText = async (text, key) => {
    try {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            await navigator.clipboard.writeText(text);
        } else {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
        }
        copiedKey.value = key;
        setTimeout(() => {
            if (copiedKey.value === key) {
                copiedKey.value = '';
            }
        }, 2000);
    } catch (err) {
        console.error('Failed to copy to clipboard', err);
    }
};

const openEmail = () => {
    window.open('https://mail.google.com/mail/?view=cm&fs=1&to=support@truckbill.in', '_blank', 'noopener,noreferrer');
};

const handleEmailCardClick = (e) => {
    if (e?.target?.closest('button')) {
        return;
    }
    openEmail();
};

const handleKeyDown = (e) => {
    if (e.key === 'Escape' && isHelpModalOpen.value) {
        isHelpModalOpen.value = false;
    }
};

onMounted(() => {
    window.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeyDown);
});
</script>

<style scoped>
/* Responsive Font Sizing (Bootstrap doesn't have 13px / 9px font size utilities) */
.app-footer {
    font-size: 13px;
    overflow-x: hidden !important;
}

.truckbill-footer-content {
    font-size: 13px;
    line-height: 1.45;
}

.truckbill-footer-content .badge {
    font-size: 10.5px !important;
    padding: 2px 7px !important;
    line-height: 1.2 !important;
    vertical-align: middle;
}

/* Footer Link color (overrides global .app-shell .app-footer a) */
.app-footer a.footer-link {
    color: var(--bs-secondary, #6c757d) !important;
    transition: color 0.15s ease-in-out;
}

.app-footer a.footer-link:hover {
    color: var(--bs-dark, #212529) !important;
}

/* Tablet view (768px - 1199.98px): 9px font size to keep ample space between 3 sections */
@media (min-width: 768px) and (max-width: 1199.98px) {
    .app-footer,
    .truckbill-footer-content {
        font-size: 9px !important;
    }

    .truckbill-footer-content .badge {
        font-size: 8px !important;
        padding: 1px 4.5px !important;
    }
}

/* Mobile view (< 768px) */
@media (max-width: 767.98px) {
    .app-footer,
    .truckbill-footer-content {
        font-size: 9.5px !important;
    }
}
</style>
