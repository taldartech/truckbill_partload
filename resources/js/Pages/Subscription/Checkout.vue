<template>
    <AdminLayout :title="isRenewal ? 'Renew plan' : 'Buy add-ons'">
        <Link :href="`/subscription/manage?company_id=${company.id}`" class="btn btn-link mb-3 text-decoration-none">
            <i class="fas fa-arrow-left me-1"></i> Back to plan details
        </Link>

        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div>
                <h4 class="fw-bold mb-1">{{ isRenewal ? 'Renew plan' : 'Buy add-ons' }}</h4>
                <p class="text-muted mb-0">{{ company.name }} — configure your order and pay securely.</p>
            </div>
            <StatusBadge :status="subscription?.display_status ?? 'none'" />
        </div>

        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Catalog pricing</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span>Annual base plan</span>
                            <span>₹{{ formatMoney(catalogData.base_price) }}</span>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span>Branches included</span>
                            <span>{{ catalogData.included_branches ?? '—' }}</span>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span>Users per branch</span>
                            <span>{{ catalogData.users_per_branch ?? '—' }}</span>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span>Branch add-on</span>
                            <span>₹{{ formatMoney(catalogData.addon_branch_price) }}/yr</span>
                        </div>
                        <div class="d-flex justify-content-between py-2">
                            <span>User add-on</span>
                            <span>₹{{ formatMoney(catalogData.addon_user_price) }}/yr</span>
                        </div>
                        <p v-if="!isRenewal && remainingDays" class="text-success small mt-3 mb-0">
                            Add-ons are charged only for the remaining {{ remainingDays }} day(s) of your current plan
                            (annual price × days ÷ 365).
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            {{ isRenewal ? 'Renew your subscription' : 'Add capacity to your plan' }}
                        </h5>
                    </div>
                    <div class="card-body">
                        <div v-if="isRenewal && usage" class="alert alert-warning">
                            Current usage: {{ usage.branches }} branch(es), {{ usage.total_users }} user(s).
                            Renewal must cover this usage — add the required add-ons below or remove branches/users first.
                        </div>
                        <div v-else-if="!isRenewal && remainingDays" class="alert alert-info">
                            Your plan ends on {{ formatDate(subscription?.ends_on) }}.
                            Add-on charges are prorated for {{ remainingDays }} remaining day(s).
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="addon_branches">
                                    Extra branches<span v-if="isRenewal && minBranches > 0"> (min {{ minBranches }})</span>
                                </label>
                                <input
                                    id="addon_branches"
                                    v-model.number="addonBranches"
                                    type="number"
                                    class="form-control"
                                    :min="isRenewal ? minBranches : 0"
                                    @input="onAddonChange"
                                />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="addon_users">
                                    Extra users<span v-if="isRenewal && minUsers > 0"> (min {{ minUsers }})</span>
                                </label>
                                <input
                                    id="addon_users"
                                    v-model.number="addonUsers"
                                    type="number"
                                    class="form-control"
                                    :min="isRenewal ? minUsers : 0"
                                    @input="onAddonChange"
                                />
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="promo_code">Promo code</label>
                            <div class="input-group">
                                <input
                                    id="promo_code"
                                    v-model="promoCode"
                                    type="text"
                                    class="form-control"
                                    placeholder="Enter code"
                                    @input="onPromoInput"
                                />
                                <button type="button" class="btn btn-outline-secondary" @click="validatePromo">
                                    Apply
                                </button>
                            </div>
                            <div v-if="promoError" class="alert alert-danger py-2 mt-2 mb-0">{{ promoError }}</div>
                            <div v-if="promoMessage" class="alert alert-success py-2 mt-2 mb-0">{{ promoMessage }}</div>
                        </div>

                        <div v-if="quoteErrors" class="alert alert-danger py-2">{{ quoteErrors }}</div>

                        <div class="border rounded p-3 mb-3 bg-light">
                            <div class="text-muted small mb-2">Order summary</div>
                            <div v-if="loadingQuote" class="text-muted">Calculating quote…</div>
                            <template v-else>
                                <div
                                    v-for="(item, index) in lineItems"
                                    :key="index"
                                    class="d-flex justify-content-between py-1"
                                    :class="{ 'text-success': Number(item.amount) < 0 }"
                                >
                                    <span>{{ item.label }}</span>
                                    <span>
                                        {{ Number(item.amount) < 0 ? '−' : '' }}₹{{ formatMoney(Math.abs(item.amount)) }}
                                    </span>
                                </div>
                                <div v-if="lineItems.length === 0" class="text-muted">No line items yet</div>
                            </template>
                            <div v-if="!loadingQuote" class="d-flex justify-content-between py-1 border-top mt-2 pt-2">
                                <span>GST ({{ gstRatePercent }}%)</span>
                                <span>₹{{ gstAmount }}</span>
                            </div>
                            <div class="d-flex justify-content-between fw-bold border-top pt-2 mt-2">
                                <span>Total payable</span>
                                <span>₹{{ quoteTotal }}</span>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="btn btn-primary"
                            :disabled="!canCheckout || paying"
                            @click="pay(company.name)"
                        >
                            <i class="fas fa-lock me-1"></i>
                            {{ paying ? 'Processing…' : 'Pay securely with Razorpay' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import StatusBadge from '@/Components/Subscription/StatusBadge.vue';
import { useSubscriptionCheckout } from '@/composables/useSubscriptionCheckout';

const props = defineProps({
    company: Object,
    subscription: Object,
    catalog: Object,
    purchaseType: String,
    usage: Object,
    minimumAddons: Object,
    remainingDays: Number,
    includeTrucks: {
        type: Boolean,
        default: false,
    },
});

const catalogData = computed(() => props.catalog?.catalog ?? {});

const {
    addonBranches,
    addonUsers,
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
    isRenewal,
    onAddonChange,
    onPromoInput,
    validatePromo,
    pay,
} = useSubscriptionCheckout({
    companyId: props.company.id,
    purchaseType: props.purchaseType,
    manageUrl: `/subscription/manage?company_id=${props.company.id}`,
    minimumAddons: props.minimumAddons ?? {},
    includeTrucks: props.includeTrucks,
});

function formatDate(value) {
    if (!value) return '—';

    return new Date(value).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

function formatMoney(value) {
    return Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
</script>
