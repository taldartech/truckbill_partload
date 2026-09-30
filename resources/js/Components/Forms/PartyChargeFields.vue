<template>
    <div class="row g-2">
        <div v-for="field in PARTY_CHARGE_FIELDS" :key="field.amount" class="col-md-4">
            <label :class="small ? 'form-label small mb-1' : 'form-label'">{{ field.label }}</label>
            <div class="input-group" :class="{ 'input-group-sm': small }">
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    :max="form[field.type] === CHARGE_TYPE_PERCENTAGE ? 100 : null"
                    class="form-control"
                    v-model="form[field.amount]"
                    :placeholder="form[field.type] === CHARGE_TYPE_PERCENTAGE ? '0 %' : '0.00'"
                >
                <select class="form-select party-charge-fields__type" v-model="form[field.type]">
                    <option :value="CHARGE_TYPE_FIXED">₹ Fixed</option>
                    <option :value="CHARGE_TYPE_PERCENTAGE">% of Freight</option>
                </select>
            </div>
            <div v-if="errorFor(field.amount)" class="text-danger small mt-1">{{ errorFor(field.amount) }}</div>
            <div v-if="errorFor(field.type)" class="text-danger small mt-1">{{ errorFor(field.type) }}</div>
        </div>
    </div>
</template>

<script setup>
import {
    CHARGE_TYPE_FIXED,
    CHARGE_TYPE_PERCENTAGE,
    PARTY_CHARGE_FIELDS,
} from '@/utils/partyCharges';

defineProps({
    form: { type: Object, required: true },
    errorFor: { type: Function, default: () => '' },
    small: { type: Boolean, default: false },
});
</script>

<style scoped>
.party-charge-fields__type {
    flex: 0 0 auto;
    width: auto;
    max-width: 8.5rem;
}
</style>
