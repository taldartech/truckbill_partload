import { onMounted, onUnmounted, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import http from '@/utils/http';

function loadRazorpayScript() {
    return new Promise((resolve, reject) => {
        if (window.Razorpay) {
            resolve(window.Razorpay);
            return;
        }

        const script = document.createElement('script');
        script.src = 'https://checkout.razorpay.com/v1/checkout.js';
        script.onload = () => resolve(window.Razorpay);
        script.onerror = reject;
        document.head.appendChild(script);
    });
}

export function useSubscriptionCheckout({
    companyId,
    purchaseType,
    manageUrl,
    minimumAddons: minimumAddonsInput = {},
    includeTrucks = false,
}) {
    const minimumAddons = minimumAddonsInput ?? {};
    const addonBranches = ref(Number(minimumAddons.addon_branches ?? 0));
    const addonUsers = ref(Number(minimumAddons.addon_users ?? 0));
    const addonTrucks = ref(Number(minimumAddons.addon_trucks ?? 0));
    const promoCode = ref('');
    const promoValidated = ref(false);
    const promoMessage = ref('');
    const promoError = ref('');
    const quoteErrors = ref('');
    const lineItems = ref([]);
    const gstAmount = ref('0.00');
    const gstRatePercent = ref(0);
    const quoteTotal = ref('0.00');
    const canCheckout = ref(false);
    const paying = ref(false);
    const loadingQuote = ref(false);

    let quoteTimer = null;

    const minBranches = Number(minimumAddons.addon_branches ?? 0);
    const minUsers = Number(minimumAddons.addon_users ?? 0);
    const minTrucks = Number(minimumAddons.addon_trucks ?? 0);
    const isRenewal = purchaseType === 'renewal';

    function payload() {
        return {
            company_id: companyId,
            purchase_type: purchaseType,
            addon_branches: Number(addonBranches.value || 0),
            addon_users: Number(addonUsers.value || 0),
            addon_trucks: includeTrucks ? Number(addonTrucks.value || 0) : 0,
            addon_lr_credits: 0,
            promo_code: promoCode.value || null,
            promo_validated: promoValidated.value,
        };
    }

    async function fetchQuote() {
        loadingQuote.value = true;
        canCheckout.value = false;

        try {
            const { data: quote } = await http.post('/subscription/quote', payload());

            quoteErrors.value = (quote.blocking_errors || []).join(' ');
            lineItems.value = quote.line_items || [];
            gstAmount.value = Number(quote.gst_amount || 0).toFixed(2);
            gstRatePercent.value = Number(quote.gst_rate_percent || 0);
            quoteTotal.value = Number(quote.total_amount || 0).toFixed(2);
            canCheckout.value = !!quote.can_checkout;
        } catch (error) {
            quoteErrors.value = error.response?.data?.message || 'Unable to load pricing. Please try again later.';
            lineItems.value = [];
            gstAmount.value = '0.00';
            gstRatePercent.value = 0;
            quoteTotal.value = '0.00';
            canCheckout.value = false;
        } finally {
            loadingQuote.value = false;
        }
    }

    function scheduleQuote() {
        clearTimeout(quoteTimer);
        quoteTimer = setTimeout(fetchQuote, 300);
    }

    function onAddonChange() {
        scheduleQuote();
    }

    function onPromoInput() {
        promoValidated.value = false;
        promoMessage.value = '';
        promoError.value = '';
    }

    async function validatePromo() {
        const code = promoCode.value.trim();
        promoError.value = '';
        promoMessage.value = '';

        if (!code) {
            promoError.value = 'Enter a promo code first.';
            return;
        }

        try {
            const { data } = await http.post('/subscription/promo/validate', { code });
            promoValidated.value = !!data.valid;

            if (data.valid) {
                promoMessage.value = 'Promo code applied successfully.';
            } else {
                promoError.value = data.message || 'Invalid promo code.';
            }
        } catch (error) {
            promoValidated.value = false;
            promoError.value = error.response?.data?.message || 'Invalid promo code.';
        }

        fetchQuote();
    }

    async function pay() {
        paying.value = true;

        try {
            const { data: order } = await http.post('/subscription/checkout', payload());

            if (!order.razorpay_order_id) {
                quoteErrors.value = 'Could not start checkout. Please try again.';
                paying.value = false;
                return;
            }

            const Razorpay = await loadRazorpayScript();

            const rzp = new Razorpay({
                key: order.razorpay_key,
                amount: order.razorpay_amount_paise,
                currency: 'INR',
                order_id: order.razorpay_order_id,
                description: order.description || 'TruckBill subscription',
                prefill: order.prefill || {},
                notes: order.notes || {},
                handler: async (response) => {
                    await http.post('/subscription/callback', response);
                    router.visit(manageUrl);
                },
                modal: {
                    ondismiss: () => {
                        paying.value = false;
                    },
                },
            });

            rzp.open();
        } catch (error) {
            quoteErrors.value = error.response?.data?.message || 'Checkout failed. Please try again.';
            paying.value = false;
        }
    }

    onMounted(fetchQuote);
    onUnmounted(() => clearTimeout(quoteTimer));

    return {
        addonBranches,
        addonUsers,
        addonTrucks,
        promoCode,
        promoMessage,
        promoError,
        quoteErrors,
        lineItems,
        gstAmount,
        gstRatePercent,
        quoteTotal,
        canCheckout,
        paying,
        loadingQuote,
        minBranches,
        minUsers,
        minTrucks,
        isRenewal,
        onAddonChange,
        onPromoInput,
        validatePromo,
        pay,
    };
}
