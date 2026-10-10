<template>
    <footer class="app-footer bg-white border-top">
        <div class="container-fluid">
            <div class="truckbill-footer-content">
                <!-- Branding: TruckBill – A Product of Taldar Tech Consultancy -->
                <div class="footer-col-brand d-inline-flex align-items-center text-nowrap">
                    <span class="fw-bold text-dark">TruckBill</span>
                    <span class="footer-separator fw-semibold" style="color: #475569;">—</span>
                    <span class="d-inline-flex align-items-center">
                        <span class="text-secondary">A Product of</span>
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
                <nav class="footer-col-nav d-inline-flex align-items-center justify-content-center text-nowrap" aria-label="Footer Navigation">
                    <a
                        href="#helpSupportModal"
                        class="footer-link fw-medium"
                        role="button"
                        @click.prevent="isHelpModalOpen = true"
                    >
                        Help &amp; Support
                    </a>
                    <span class="footer-separator" aria-hidden="true">•</span>
                    <a
                        href="https://www.truckbill.in/policies/privacy-policy.html"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="footer-link fw-medium"
                    >
                        Privacy Policy
                    </a>
                    <span class="footer-separator" aria-hidden="true">•</span>
                    <a
                        href="https://www.truckbill.in/policies/terms-and-conditions.html"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="footer-link fw-medium"
                    >
                        Terms &amp; Conditions
                    </a>
                    <span class="footer-separator" aria-hidden="true">•</span>
                    <a
                        href="https://www.truckbill.in/#contact"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="footer-link fw-medium"
                    >
                        Contact Us
                    </a>
                </nav>

                <!-- Copyright & Dynamic Application Version -->
                <div class="footer-col-meta d-inline-flex align-items-center text-nowrap gap-2">
                    <span class="text-secondary">&copy; {{ currentYear }} TruckBill. All rights reserved.</span>
                    <span class="badge bg-success rounded-pill fw-semibold">{{ displayVersion }}</span>
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
                    <div class="tb-modal-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="tb-header-icon-circle">
                                <!-- Green Headphone/Headset SVG identical to reference image -->
                                <svg width="24" height="24" viewBox="0 0 16 16" fill="#16a34a" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M8 1a5 5 0 0 0-5 5v1h1a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V6a6 6 0 1 1 12 0v6a2.5 2.5 0 0 1-2.5 2.5H9.366a1 1 0 0 1-.866.5h-1a1 1 0 1 1 0-2h1a1 1 0 0 1 .866.5H11.5A1.5 1.5 0 0 0 13 12h-1a1 1 0 0 1-1-1V8a1 1 0 0 1 1-1h1V6a5 5 0 0 0-5-5"/>
                                </svg>
                            </div>
                            <div>
                                <h5 id="helpSupportModalLabel" class="tb-modal-title mb-0">Help &amp; Support</h5>
                                <div class="tb-modal-subtitle">
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
                            class="tb-contact-box tb-email-box mb-3"
                            @click="handleEmailCardClick"
                            title="Click to compose email (Gmail/Webmail)"
                        >
                            <a
                                href="https://mail.google.com/mail/?view=cm&fs=1&to=support@truckbill.in"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="d-flex align-items-center gap-3 text-decoration-none min-w-0 flex-grow-1"
                            >
                                <div class="tb-icon-circle tb-email-circle flex-shrink-0">
                                    <i class="fa-solid fa-envelope text-white"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="tb-contact-label">EMAIL SUPPORT</div>
                                    <div class="tb-email-val text-truncate">
                                        support@truckbill.in
                                    </div>
                                </div>
                            </a>
                            <button
                                type="button"
                                class="tb-copy-btn tb-copy-email-btn"
                                @click.stop="copyText('support@truckbill.in', 'email')"
                            >
                                <svg v-if="copiedKey !== 'email'" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                </svg>
                                <svg v-else width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                                <span>{{ copiedKey === 'email' ? 'Copied' : 'Copy' }}</span>
                            </button>
                        </div>

                        <!-- Helpline & Contact Card -->
                        <div class="tb-contact-box tb-phone-box mb-3">
                            <div class="d-flex align-items-center gap-3 min-w-0 flex-grow-1">
                                <div class="tb-icon-circle tb-phone-circle flex-shrink-0">
                                    <i class="fa-solid fa-phone text-white"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="tb-contact-label">HELPLINE &amp; CONTACT</div>
                                    <div class="tb-phone-val text-truncate">
                                        <a href="tel:+919784451256" class="tb-phone-link text-decoration-none">+91 97844 51256</a>
                                        <span class="text-muted fw-normal mx-1">•</span>
                                        <a href="tel:+919784946004" class="tb-phone-link text-decoration-none">+91 97849 46004</a>
                                    </div>
                                </div>
                            </div>
                            <button
                                type="button"
                                class="tb-copy-btn tb-copy-phone-btn"
                                @click.stop="copyText('+91 97844 51256, +91 97849 46004', 'phone')"
                            >
                                <svg v-if="copiedKey !== 'phone'" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                </svg>
                                <svg v-else width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
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
                            class="tb-btn-close-bottom"
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
    if (e?.target?.closest('.tb-copy-btn')) {
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
/* Base Footer Sizing & Scrollbar Prevention */
.app-footer {
    padding: 0.5rem 1rem !important;
    font-size: 12.5px;
    overflow-x: hidden !important;
    max-width: 100%;
}

.truckbill-footer-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    font-size: 12.5px;
    line-height: 1.45;
}

.truckbill-footer-content .footer-col-brand,
.truckbill-footer-content .footer-col-nav,
.truckbill-footer-content .footer-col-meta {
    display: inline-flex !important;
    align-items: center !important;
    line-height: 1.45;
}

.footer-separator {
    margin: 0 0.45rem;
    color: #94a3b8;
    display: inline-flex;
    align-items: center;
}

/* Footer Links (overrides global .app-shell .app-footer a) */
.app-footer a.footer-link,
.footer-link {
    color: #475569 !important;
    text-decoration: none !important;
    font-weight: 500;
    transition: color 0.15s ease-in-out;
}

.app-footer a.footer-link:hover,
.footer-link:hover {
    color: #0f172a !important;
    text-decoration: none !important;
}

/* Badge size aligned with text */
.truckbill-footer-content .badge {
    font-size: 11px !important;
    padding: 2.5px 7px !important;
    line-height: 1.2 !important;
    vertical-align: middle;
}

/* Middle / Tablet screens (Bootstrap md & lg up to 1199px): Font chota taaki tino ke beech achha space maintain rahe */
@media (min-width: 768px) and (max-width: 1199.98px) {
    .app-footer,
    .truckbill-footer-content,
    .truckbill-footer-content strong,
    .truckbill-footer-content span,
    .truckbill-footer-content a,
    .truckbill-footer-content nav,
    .truckbill-footer-content div {
        font-size: 10px !important;
    }

    .truckbill-footer-content .badge {
        font-size: 8.5px !important;
        padding: 1.5px 5px !important;
    }

    .footer-separator {
        margin: 0 0.25rem !important;
    }
}

/* Smaller tablets (768px - 991.98px) for maximum responsiveness and spacing */
@media (min-width: 768px) and (max-width: 991.98px) {
    .app-footer,
    .truckbill-footer-content,
    .truckbill-footer-content strong,
    .truckbill-footer-content span,
    .truckbill-footer-content a,
    .truckbill-footer-content nav,
    .truckbill-footer-content div {
        font-size: 9px !important;
    }

    .truckbill-footer-content .badge {
        font-size: 8px !important;
        padding: 1px 4.5px !important;
    }

    .footer-separator {
        margin: 0 0.2rem !important;
    }
}

/* Mobile view (< 768px): Exactly 3 compact rows, Contact Us stays in line 1 */
@media (max-width: 767.98px) {
    .app-footer {
        padding: 4px 6px !important;
    }

    .truckbill-footer-content {
        flex-direction: column !important;
        gap: 2px !important;
        text-align: center !important;
    }

    .truckbill-footer-content,
    .truckbill-footer-content strong,
    .truckbill-footer-content span,
    .truckbill-footer-content a,
    .truckbill-footer-content nav,
    .truckbill-footer-content div {
        font-size: 10px !important;
        line-height: 1.35 !important;
    }

    .truckbill-footer-content .footer-col-nav {
        order: 1 !important;
        flex-wrap: nowrap !important;
        white-space: nowrap !important;
        justify-content: center !important;
        width: 100% !important;
    }

    .truckbill-footer-content .footer-col-brand {
        order: 2 !important;
        justify-content: center !important;
        width: 100% !important;
        white-space: nowrap !important;
    }

    .truckbill-footer-content .footer-col-meta {
        order: 3 !important;
        justify-content: center !important;
        width: 100% !important;
        white-space: nowrap !important;
        gap: 4px !important;
    }

    .truckbill-footer-content .badge {
        font-size: 8px !important;
        padding: 1px 4.5px !important;
    }

    .footer-separator {
        margin: 0 0.18rem !important;
    }
}

@media (max-width: 350px) {
    .truckbill-footer-content,
    .truckbill-footer-content strong,
    .truckbill-footer-content span,
    .truckbill-footer-content a,
    .truckbill-footer-content nav,
    .truckbill-footer-content div {
        font-size: 8.8px !important;
    }
    .footer-separator {
        margin: 0 0.12rem !important;
    }
}

/* Modal Header */
.tb-modal-header {
    background: linear-gradient(180deg, #ecfdf5 0%, #f0fdf4 65%, #ffffff 100%);
    padding: 20px 24px 14px 24px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.tb-header-icon-circle {
    width: 44px;
    height: 44px;
    background: #ffffff;
    border-radius: 50%;
    border: 1px solid #d1fae5;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.15);
    flex-shrink: 0;
}

.tb-modal-title {
    font-size: 1.15rem;
    font-weight: 700;
    color: #111827;
}

.tb-modal-subtitle {
    font-size: 0.8rem;
    color: #6b7280;
    margin-top: 2px;
}

/* Support Channel Cards */
.tb-contact-box {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 12px 16px;
    border-radius: 12px;
}

.tb-email-box {
    background-color: #f0fdf4 !important;
    border: 1px solid #bbf7d0 !important;
    cursor: pointer;
    transition: background-color 0.15s ease;
}

.tb-email-box:hover {
    background-color: #e6fced !important;
}

.tb-phone-box {
    background-color: #eff6ff !important;
    border: 1px solid #bfdbfe !important;
}

.tb-icon-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.05rem;
    flex-shrink: 0;
}

.tb-email-circle {
    background-color: #16a34a;
}

.tb-phone-circle {
    background-color: #2563eb;
}

.tb-contact-label {
    font-size: 0.6875rem;
    font-weight: 700;
    color: #4b5563;
    letter-spacing: 0.5px;
    line-height: 1.2;
    margin-bottom: 2px;
}

.tb-contact-val {
    font-size: 0.95rem;
    font-weight: 700;
}

.tb-email-val {
    font-size: 0.95rem;
    font-weight: 700;
    color: #047857 !important;
    text-decoration: none !important;
}

.tb-phone-val {
    font-size: 0.95rem;
    font-weight: 700;
    color: #2563eb;
}

.tb-phone-link {
    color: #2563eb !important;
    text-decoration: none !important;
}

/* Copy Buttons */
.tb-copy-btn {
    background-color: #ffffff !important;
    border-radius: 8px !important;
    padding: 6px 14px !important;
    font-size: 0.8125rem !important;
    font-weight: 600 !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 6px !important;
    cursor: pointer !important;
    transition: all 0.15s ease !important;
    white-space: nowrap !important;
    flex-shrink: 0 !important;
}

.tb-copy-email-btn {
    border: 1px solid #16a34a !important;
    color: #047857 !important;
}

.tb-copy-email-btn:hover {
    background-color: #f0fdf4 !important;
    color: #047857 !important;
}

.tb-copy-phone-btn {
    border: 1px solid #2563eb !important;
    color: #2563eb !important;
}

.tb-copy-phone-btn:hover {
    background-color: #eff6ff !important;
    color: #2563eb !important;
}

/* Close button matching screenshot exactly */
.tb-btn-close-bottom {
    background-color: #e9ecef !important;
    border: 1px solid #dee2e6 !important;
    color: #16a34a !important;
    font-weight: 700 !important;
    border-radius: 8px !important;
    padding: 8px 24px !important;
    font-size: 0.9rem !important;
    width: 100% !important;
    cursor: pointer !important;
    transition: all 0.15s ease-in-out !important;
}

.tb-btn-close-bottom:hover {
    background-color: #dee2e6 !important;
    color: #15803d !important;
}
</style>
