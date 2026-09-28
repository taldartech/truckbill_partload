<template>
    <AdminLayout title="Quick Search">
        <div class="app-index-page app-index-page--standard">
            <div class="card app-index-list-card">
                <div class="card-header">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-search me-2 text-primary"></i>Quick LR / Bilty Search
                    </h3>
                </div>

                <div class="card-body border-bottom p-3">
                    <AppIndexSearchInput v-model="localQuery" input-id="qs_input" placeholder="LR / Bilty No"
                        max-width="480px" size="md" />
                </div>

                <div class="card-body p-0">
                    <div v-if="query === ''" class="text-center text-muted py-5">
                        <i class="fas fa-search fa-2x mb-3 d-block opacity-25"></i>
                        <p class="mb-0">Enter a LR / Bilty number above to search.</p>
                    </div>

                    <div v-else-if="bilties.length === 0" class="text-center text-muted py-5">
                        <i class="fas fa-inbox fa-2x mb-3 d-block opacity-25"></i>
                        <p class="mb-1">No LR / Bilty found for <strong>"{{ query }}"</strong></p>
                        <p class="small mb-0">Try a different LR / Bilty number.</p>
                    </div>

                    <template v-else>

                        <!-- Exact match detail panel -->
                        <div v-if="detail" class="detail-panel border-bottom">

                            <!-- Bilty Header -->
                            <div
                                class="detail-section-header px-3 py-2 d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <span><i class="fas fa-file-invoice me-2"></i>Bilty — {{ detail.bilty.bilty_number
                                        }}</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-white text-dark fw-semibold p-2">{{ detail.bilty.status ||
                                        'Unknown' }} </span>
                                    <BiltyPrintButton :bilty-id="detail.bilty.id" button-class="btn btn-sm btn-primary">
                                        <i class="fas fa-print me-1"></i>Print
                                    </BiltyPrintButton>
                                </div>
                            </div>

                            <!-- Bilty Core Info -->
                            <div class="px-3 py-3">
                                <div class="row g-3">
                                    <div class="col-6 col-md-3">
                                        <div class="detail-label">Date</div>
                                        <div class="detail-value" :class="{ 'na': !detail.bilty.date }">
                                            {{ detail.bilty.date || 'Not available' }}
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="detail-label">From → To</div>
                                        <div class="detail-value"
                                            :class="{ 'na': !detail.bilty.from_station && !detail.bilty.to_station }">
                                            {{ detail.bilty.from_station?.name || 'N/A' }} → {{
                                                detail.bilty.to_station?.name || 'N/A' }}
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="detail-label">Consignor</div>
                                        <div class="detail-value" :class="{ 'na': !detail.bilty.consignor }">
                                            <div>{{ detail.bilty.consignor?.name || 'Not available' }}</div>
                                            <div class="small text-muted fw-normal"
                                                v-if="detail.bilty.consignor?.gst_number">GST: {{
                                                    detail.bilty.consignor.gst_number }}</div>
                                            <div class="small text-muted fw-normal"
                                                v-if="detail.bilty.consignor?.mobile">Mob: {{
                                                    detail.bilty.consignor.mobile }}</div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="detail-label">Consignee</div>
                                        <div class="detail-value" :class="{ 'na': !detail.bilty.consignee }">
                                            <div>{{ detail.bilty.consignee?.name || 'Not available' }}</div>
                                            <div class="small text-muted fw-normal"
                                                v-if="detail.bilty.consignee?.gst_number">GST: {{
                                                    detail.bilty.consignee.gst_number }}</div>
                                            <div class="small text-muted fw-normal"
                                                v-if="detail.bilty.consignee?.mobile">Mob: {{
                                                    detail.bilty.consignee.mobile }}</div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="detail-label">Payment Type</div>
                                        <div class="detail-value" :class="{ 'na': !detail.bilty.payment_type }">
                                            {{ detail.bilty.payment_type || 'Not available' }}
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="detail-label">Invoice No</div>
                                        <div class="detail-value" :class="{ 'na': !detail.bilty.invoice_number }">
                                            {{ detail.bilty.invoice_number || 'Not available' }}
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="detail-label">Declared Value</div>
                                        <div class="detail-value" :class="{ 'na': !detail.bilty.declared_value }">
                                            {{ detail.bilty.declared_value ? '₹ ' +
                                                formatMoney(detail.bilty.declared_value) : 'Not available' }}
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="detail-label">Actual Weight</div>
                                        <div class="detail-value" :class="{ 'na': !detail.bilty.weight_actual }">
                                            {{ detail.bilty.weight_actual ? detail.bilty.weight_actual + ' kg' : 'Not available' }}
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3" v-if="detail.bilty.private_marka">
                                        <div class="detail-label">Private Marka</div>
                                        <div class="detail-value">{{ detail.bilty.private_marka }}</div>
                                    </div>
                                    <div class="col-6 col-md-3" v-if="detail.bilty.delivery_at">
                                        <div class="detail-label">Delivery At</div>
                                        <div class="detail-value">{{ detail.bilty.delivery_at }}</div>
                                    </div>
                                    <div class="col-6 col-md-3" v-if="detail.bilty.vendor">
                                        <div class="detail-label">Vendor</div>
                                        <div class="detail-value">{{ detail.bilty.vendor?.name }}</div>
                                    </div>
                                    <div class="col-6 col-md-3" v-if="detail.bilty.gst_liability_by">
                                        <div class="detail-label">GST Liability By</div>
                                        <div class="detail-value">{{ detail.bilty.gst_liability_by }}</div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="detail-label">Total Amount</div>
                                        <div class="detail-value fw-bold text-success"
                                            :class="{ 'na': !detail.bilty.amount }">
                                            {{ detail.bilty.amount ? '₹ ' + formatMoney(detail.bilty.amount) : 'Not available' }}
                                        </div>
                                    </div>
                                    <div class="col-12" v-if="detail.bilty.remark">
                                        <div class="detail-label">Remark</div>
                                        <div class="detail-value text-muted">{{ detail.bilty.remark }}</div>
                                    </div>
                                </div>

                                <!-- Bilty Charges & Breakdown -->
                                <div class="detail-subsection-title mt-4">Charges & Breakdown</div>
                                <div class="row g-2">
                                    <div class="col-6 col-sm-4 col-md-3" v-if="detail.bilty.freight != null">
                                        <div class="charge-card p-2 rounded">
                                            <div class="detail-label">Freight</div>
                                            <div class="fw-semibold">₹ {{ formatMoney(detail.bilty.freight) }}</div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-sm-4 col-md-3" v-if="detail.bilty.bilty_charge != null">
                                        <div class="charge-card p-2 rounded">
                                            <div class="detail-label">Bilty Charge</div>
                                            <div class="fw-semibold">₹ {{ formatMoney(detail.bilty.bilty_charge) }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-sm-4 col-md-3" v-if="Number(detail.bilty.surcharge ?? 0) > 0">
                                        <div class="charge-card p-2 rounded">
                                            <div class="detail-label">Surcharge</div>
                                            <div class="fw-semibold">₹ {{ formatMoney(detail.bilty.surcharge) }}</div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-sm-4 col-md-3" v-if="Number(detail.bilty.cartage ?? 0) > 0">
                                        <div class="charge-card p-2 rounded">
                                            <div class="detail-label">
                                                Cartage{{ detail.bilty.cartage_type ? ' (' + detail.bilty.cartage_type +
                                                ')' : '' }}
                                            </div>
                                            <div class="fw-semibold">₹ {{ formatMoney(detail.bilty.cartage) }}</div>
                                            <div class="small text-muted" v-if="detail.bilty.vendor?.name">Vendor: {{
                                                detail.bilty.vendor.name }}</div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-sm-4 col-md-3"
                                        v-if="Number(detail.bilty.handling_charge ?? 0) > 0">
                                        <div class="charge-card p-2 rounded">
                                            <div class="detail-label">Handling Charge</div>
                                            <div class="fw-semibold">₹ {{ formatMoney(detail.bilty.handling_charge) }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-sm-4 col-md-3"
                                        v-if="Number(detail.bilty.risk_dd_charge ?? 0) > 0">
                                        <div class="charge-card p-2 rounded">
                                            <div class="detail-label">Risk / DD Charge</div>
                                            <div class="fw-semibold">₹ {{ formatMoney(detail.bilty.risk_dd_charge) }}
                                            </div>
                                        </div>
                                    </div>
                                    <template v-for="(ac, idx) in getBiltyAddonCharges(detail.bilty)" :key="idx">
                                        <div class="col-6 col-sm-4 col-md-3" v-if="Number(ac.amount ?? 0) > 0">
                                            <div class="charge-card p-2 rounded">
                                                <div class="detail-label">{{ ac.particulars || ac.description || 'Addon Charge' }}</div>
                                                <div class="fw-semibold">₹ {{ formatMoney(ac.amount) }}</div>
                                            </div>
                                        </div>
                                    </template>
                                    <div class="col-6 col-sm-4 col-md-3" v-if="Number(detail.bilty.gst ?? 0) > 0">
                                        <div class="charge-card p-2 rounded">
                                            <div class="detail-label">GST ({{ detail.bilty.gst }}%)</div>
                                            <div class="fw-semibold">
                                                ₹ {{ formatMoney((Number(detail.bilty.cgst ?? 0) + Number(detail.bilty.sgst ?? 0) + Number(detail.bilty.igst ?? 0))) }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-sm-4 col-md-3"
                                        v-if="Number(detail.bilty.less_advance_received ?? 0) > 0">
                                        <div class="charge-card p-2 rounded text-danger">
                                            <div class="detail-label text-danger">Less Advance{{
                                                detail.bilty.advance_payment_mode ? ' (' +
                                                    detail.bilty.advance_payment_mode + ')' : '' }}</div>
                                            <div class="fw-semibold">- ₹ {{
                                                formatMoney(detail.bilty.less_advance_received) }}</div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-sm-4 col-md-3">
                                        <div class="charge-card p-2 rounded border-success">
                                            <div class="detail-label text-success fw-bold">Total Amount</div>
                                            <div class="fw-bold fs-6 text-success"
                                                :class="{ 'na': !detail.bilty.amount }">
                                                {{ detail.bilty.amount ? '₹ ' + formatMoney(detail.bilty.amount) : 'Not available' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Goods -->
                                <div class="detail-subsection-title mt-4">Goods Details</div>
                                <template v-if="detail.bilty.bilty_details?.length">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 35px;">#</th>
                                                    <th>Goods / Item</th>
                                                    <th>Packaging</th>
                                                    <th class="text-end">Packages</th>
                                                    <th class="text-end">Actual Wt</th>
                                                    <th class="text-end">Charged Wt</th>
                                                    <th>Rate Type</th>
                                                    <th class="text-end">Rate</th>
                                                    <th class="text-end">Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="(d, idx) in detail.bilty.bilty_details" :key="d.id || idx">
                                                    <td class="text-muted">{{ idx + 1 }}</td>
                                                    <td class="fw-semibold">{{ d.good?.name || d.description || 'Not available' }}</td>
                                                    <td>{{ d.package_type?.name || d.packageType?.name || 'Not available' }}</td>
                                                    <td class="text-end">{{ d.no_of_packages != null ? d.no_of_packages : 'Not available' }}</td>
                                                    <td class="text-end">{{ d.gross_weight != null ? d.gross_weight + 'kg' : 'Not available' }}</td>
                                                    <td class="text-end">{{ d.charged_weight != null ? d.charged_weight
                                                        + ' kg' : 'Not available' }}</td>
                                                    <td>{{ d.rate_type || 'Not available' }}</td>
                                                    <td class="text-end">{{ d.rate != null ? '₹ ' + formatMoney(d.rate)
                                                        : 'Not available' }}</td>
                                                    <td class="text-end fw-semibold">{{ goodsAmount(d) != null ? '₹ ' +
                                                        formatMoney(goodsAmount(d)) : 'Not available' }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </template>
                                <p v-else class="na-text mb-0">No goods details available.</p>

                                <div class="mt-3">
                                </div>
                            </div>

                            <!-- E-way Bills -->
                            <div class="detail-section-header px-3 py-2">
                                <i class="fas fa-barcode me-2"></i>E-way Bills
                            </div>
                            <template v-if="(detail.bilty.eway_bills || detail.bilty.ewayBills)?.length">
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 40px;">#</th>
                                                <th>E-Way Bill No</th>
                                                <th>Valid Till</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(e, idx) in (detail.bilty.eway_bills || detail.bilty.ewayBills)"
                                                :key="e.id || idx">
                                                <td class="text-muted">{{ idx + 1 }}</td>
                                                <td class="fw-semibold">
                                                    <i class="fas fa-barcode me-2 text-muted"></i>{{ e.eway_bill_no ||
                                                        e.eway_bill_number || 'Not available' }}
                                                </td>
                                                <td class="text-nowrap">{{ e.eway_bill_valid_till || 'Not available' }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </template>
                            <p v-else class="na-text px-3 py-2 mb-0">No e-way bills available.</p>

                            <!-- Challans -->
                            <div
                                class="detail-section-header px-3 py-2 d-flex justify-content-between align-items-center">
                                <span>
                                    <i class="fas fa-receipt me-2"></i>
                                    Challans
                                </span>
                                <span class="badge text-end bg-light text-dark fw-semibold p-2"
                                    v-if="detail.challans?.length">{{
                                        detail.challans[0]?.status }}</span>
                            </div>
                            <template v-if="detail.challans?.length">
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Challan No</th>
                                                <th>Date</th>
                                                <th>From → To</th>
                                                <th>Vehicle & Driver</th>
                                                <th class="text-end">Truck Freight</th>
                                                <th>Charges Breakdown</th>
                                                <th class="text-end">Total Amount</th>
                                                <th class="text-end">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="c in detail.challans" :key="c.id">
                                                <td class="fw-semibold">
                                                    <div>{{ c.challan_number || 'Not available' }}</div>
                                                </td>
                                                <td>{{ c.date || 'Not available' }}</td>
                                                <td>{{ (c.from_station?.name || 'N/A') + ' → ' + (c.to_station?.name ||
                                                    'N/A') }}</td>
                                                <td>
                                                    <div>{{ c.truck?.truck_number || c.truck?.registration_number ||
                                                        'Not available' }}</div>
                                                    <div class="small text-muted" v-if="c.driver_name">Driver: {{
                                                        c.driver_name }}</div>
                                                </td>
                                                <td class="text-end">
                                                    <span v-if="c.truck_freight != null">₹ {{
                                                        formatMoney(c.truck_freight) }}</span>
                                                    <span v-else-if="c.booking_freight != null">₹ {{
                                                        formatMoney(c.booking_freight) }}</span>
                                                    <span v-else class="na-text">Not available</span>
                                                </td>
                                                <td>
                                                    <div v-if="challanChargesList(c).length"
                                                        class="d-flex flex-wrap gap-1">
                                                        <span v-for="(ch, cIdx) in challanChargesList(c)" :key="cIdx"
                                                            class="badge bg-light text-dark border small">
                                                            {{ ch.label }}: ₹{{ formatMoney(ch.amount) }}
                                                        </span>
                                                    </div>
                                                    <span v-else class="na-text">No additional charges</span>
                                                </td>
                                                <td class="text-end fw-semibold">
                                                    <div>{{ c.total_amount != null ? '₹ ' + formatMoney(c.total_amount)
                                                        : 'Not available' }}</div>
                                                    <div v-if="c.cash_received != null && Number(c.cash_received) > 0"
                                                        class="small text-success">
                                                        Adv: ₹{{ formatMoney(c.cash_received) }}
                                                    </div>
                                                    <div v-if="c.to_pay_amount != null && Number(c.to_pay_amount) > 0"
                                                        class="small text-warning">
                                                        To Pay: ₹{{ formatMoney(c.to_pay_amount) }}
                                                    </div>
                                                </td>
                                                <td class="text-end">
                                                    <button class="btn btn-sm btn-outline-primary" title="Print Challan"
                                                        @click="openPrint('/challans/' + c.id + '/print')">
                                                        <i class="fas fa-print"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </template>
                            <p v-else class="na-text px-3 py-2 mb-0">No challans linked to this bilty.</p>

                            <!-- Main Challans -->
                            <div class="detail-section-header px-3 py-2">
                                <i class="fas fa-file-alt me-2"></i>
                                Main Challans
                            </div>
                            <template v-if="(detail.main_challans || detail.mainChallans)?.length">
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Main Challan No</th>
                                                <th>Date</th>
                                                <th>From → To</th>
                                                <th>Vehicle & Driver</th>
                                                <th class="text-end">Truck Freight</th>
                                                <th>Charges Breakdown</th>
                                                <th class="text-end">Total Amount</th>
                                                <th class="text-end">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="mc in (detail.main_challans || detail.mainChallans)"
                                                :key="mc.id">
                                                <td class="fw-semibold">
                                                    <div>{{ mc.main_challan_number || 'Not available' }}</div>
                                                    <div v-if="mc.details?.length"
                                                        class="small text-muted fw-normal mt-1">
                                                        Challan: {{mc.details.map(d =>
                                                            d.challan?.challan_number).filter(Boolean).join(', ')}}
                                                    </div>
                                                </td>
                                                <td class="text-nowrap">{{ mc.date || 'Not available' }}</td>
                                                <td>{{ (mc.from_station?.name || 'N/A') + ' → ' + (mc.to_station?.name
                                                    || 'N/A') }}</td>
                                                <td>
                                                    <div>{{ mc.truck?.truck_number || mc.truck?.registration_number ||
                                                        'Not available' }}</div>
                                                    <div class="small text-muted" v-if="mc.driver_name">Driver: {{
                                                        mc.driver_name }}</div>
                                                </td>
                                                <td class="text-end">
                                                    <span v-if="mc.truck_freight != null">₹ {{
                                                        formatMoney(mc.truck_freight) }}</span>
                                                    <span v-else class="na-text">Not available</span>
                                                </td>
                                                <td>
                                                    <div v-if="challanChargesList(mc).length"
                                                        class="d-flex flex-wrap gap-1">
                                                        <span v-for="(ch, mIdx) in challanChargesList(mc)" :key="mIdx"
                                                            class="badge bg-light text-dark border small">
                                                            {{ ch.label }}: ₹{{ formatMoney(ch.amount) }}
                                                        </span>
                                                    </div>
                                                    <span v-else class="na-text">No additional charges</span>
                                                </td>
                                                <td class="text-end fw-semibold">
                                                    <div>{{ mc.to_pay_amount != null ? '₹ ' +
                                                        formatMoney(mc.to_pay_amount) : (mc.paid_amount != null ? '₹ ' +
                                                            formatMoney(mc.paid_amount) : 'Not available') }}</div>
                                                </td>
                                                <td class="text-end">
                                                    <button class="btn btn-sm btn-outline-primary"
                                                        title="Print Main Challan"
                                                        @click="openPrint('/main-challans/' + mc.id + '/print')">
                                                        <i class="fas fa-print"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </template>
                            <p v-else class="na-text px-3 py-2 mb-0">No main challans linked to this bilty.</p>

                            <!-- Invoices -->
                            <div class="detail-section-header px-3 py-2">
                                <i class="fas fa-file-invoice-dollar me-2"></i>
                                Invoices
                            </div>
                            <template v-if="detail.invoices?.length">
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Invoice No</th>
                                                <th>Date</th>
                                                <th>Party</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                                <th class="text-end">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="inv in detail.invoices" :key="inv.id">
                                                <td>{{ inv.invoice_no || 'Not available' }}</td>
                                                <td>{{ inv.date || 'Not available' }}</td>
                                                <td>{{ inv.party?.name || 'Not available' }}</td>
                                                <td>{{ inv.net_amount ? '₹ ' + inv.net_amount : 'Not available' }}</td>
                                                <td>{{ inv.status || 'Not available' }}</td>
                                                <td class="text-end">
                                                    <button class="btn btn-sm btn-outline-primary" title="Print Invoice"
                                                        @click="openPrint('/invoices/' + inv.id + '/print')">
                                                        <i class="fas fa-print"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </template>
                            <p v-else class="na-text px-3 py-2 mb-0">No invoices linked to this bilty.</p>

                            <!-- Vouchers -->
                            <div class="detail-section-header px-3 py-2">
                                <i class="fas fa-money-bill-wave me-2"></i>
                                Vouchers{{ detail.vouchers?.length ? ' (' + detail.vouchers.length + ')' : '' }}
                            </div>
                            <template v-if="detail.vouchers?.length">
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Voucher No</th>
                                                <th>Date</th>
                                                <th>Type</th>
                                                <th>Amount</th>
                                                <th>Remark</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="v in detail.vouchers" :key="v.id">
                                                <td>{{ v.voucher_number || 'Not available' }}</td>
                                                <td>{{ v.date || 'Not available' }}</td>
                                                <td>{{ v.voucher_type || 'Not available' }}</td>
                                                <td>{{ v.amount ? '₹ ' + v.amount : 'Not available' }}</td>
                                                <td>{{ v.remark || 'Not available' }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </template>
                            <p v-else class="na-text px-3 py-2 mb-0">No vouchers linked to this bilty.</p>

                        </div>

                        <div class="table-responsive" v-if="!detail">
                            <table class="table table-striped table-hover align-middle mb-0 app-index-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>LR / Bilty No</th>
                                        <th class="d-none d-sm-table-cell">Date</th>
                                        <th class="d-none d-md-table-cell">From – To</th>
                                        <th>Consignor / Consignee</th>
                                        <th class="d-none d-lg-table-cell">Vehicle</th>
                                        <th class="d-none d-md-table-cell">Amount</th>
                                        <th class="d-none d-sm-table-cell">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="b in bilties" :key="b.id" class="cursor-pointer"
                                        title="Click to view details" @click="selectBilty(b)">
                                        <td>
                                            {{ b.bilty_number || 'N/A' }}

                                        </td>
                                        <td class="text-nowrap d-none d-sm-table-cell">{{ b.date || 'Not available' }}
                                        </td>
                                        <td class="text-nowrap d-none d-md-table-cell">
                                            <span>{{ b.from_station?.name || 'N/A' }}</span>
                                            <i class="fas fa-arrow-right mx-2 text-muted small"></i>
                                            <span>{{ b.to_station?.name || 'N/A' }}</span>
                                        </td>
                                        <td>
                                            <div>S: {{ b.consignor?.name || 'Not available' }}</div>
                                            <div class="small text-muted">R: {{ b.consignee?.name || 'Not available' }}
                                            </div>
                                        </td>
                                        <td class="text-nowrap d-none d-lg-table-cell">{{ b.vehicle_number || 'Not available' }}</td>
                                        <td class="d-none d-md-table-cell">{{ b.amount || 'Not available' }}</td>
                                        <td class="d-none d-sm-table-cell">
                                            <span class="badge" :class="statusClass(b.status)">{{ b.status }}</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import BiltyPrintButton from '@/Components/BiltyPrintButton.vue';
import AppIndexSearchInput from '@/Components/Index/AppIndexSearchInput.vue';

const props = defineProps({
    bilties: { type: Array, default: () => [] },
    query: { type: String, default: '' },
    detail: { type: Object, default: null },
});

const localQuery = ref(props.query);

let debounceTimer = null;

watch(localQuery, (val) => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        router.get('/quick-search', { q: val.trim() }, {
            preserveState: true,
            replace: true,
        });
    }, 400);
});

watch(() => props.query, (val) => {
    if (val !== localQuery.value) {
        localQuery.value = val;
    }
});

const selectBilty = (b) => {
    if (!b?.bilty_number) return;
    localQuery.value = b.bilty_number;
    clearTimeout(debounceTimer);
    router.get('/quick-search', { q: b.bilty_number }, {
        preserveState: true,
    });
};


const statusClass = (status) => ({
    'bg-warning': status === 'Pending',
    'bg-info': status === 'In Transit',
    'bg-success': status === 'Delivered',
    'bg-danger': status === 'Cancelled',
});

const formatMoney = (value) => {
    if (value === null || value === undefined || value === '') return '0.00';
    const num = Number(value);
    if (isNaN(num)) return String(value);
    return num.toLocaleString('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
};

const goodsAmount = (d) => {
    if (d.rate === null || d.rate === undefined || d.rate === '') return null;
    const rate = Number(d.rate ?? 0);
    const weight = Number(d.gross_weight ?? 0);
    const chargedWeight = Number(d.charged_weight ?? 0);
    const packages = Number(d.no_of_packages ?? 0);
    const rateType = d.rate_type || 'Fixed';

    if (rateType === 'Per Actual Weight' || rateType === 'Per KG') {
        return rate * weight;
    }
    if (rateType === 'Per Charged Weight') {
        return rate * chargedWeight;
    }
    if (rateType === 'Per Package') {
        return rate * packages;
    }
    return rate;
};

const getBiltyAddonCharges = (bilty) => {
    const charges = bilty?.addon_charges;
    return Array.isArray(charges) ? charges : [];
};

const challanChargesList = (c) => {
    const list = [];
    if (Number(c.labour_charge ?? 0) > 0) list.push({ label: 'Labour', amount: c.labour_charge });
    if (Number(c.kat ?? 0) > 0) list.push({ label: 'Kat', amount: c.kat });
    if (Number(c.home_delivery_charge ?? 0) > 0) list.push({ label: 'Delivery', amount: c.home_delivery_charge });
    if (Number(c.cartage_dalali_pf ?? 0) > 0) list.push({ label: 'Cartage/PF', amount: c.cartage_dalali_pf });
    if (Number(c.other_charge ?? 0) > 0) list.push({ label: 'Other', amount: c.other_charge });
    if (Number(c.gst ?? 0) > 0) list.push({ label: 'GST', amount: c.gst });
    return list;
};

const openPrint = (url) => window.open(url, '_blank');
</script>

<style scoped>
.cursor-pointer {
    cursor: pointer;
}

.charge-card {
    background-color: #f8fafc;
    border: 1px solid #e2e8f0;
}

.bilty-table-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.5rem;
}

.detail-section-header {
    font-size: 0.875rem;
    font-weight: 600;
    background: var(--brand-gradient) !important;
    color: var(--brand-text-on-green, #ffffff) !important;
}

.detail-subsection-title {
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #64748b;
    margin-bottom: 0.4rem;
}

.detail-label {
    font-size: 0.75rem;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-bottom: 0.15rem;
}

.detail-value {
    font-size: 0.9rem;
    font-weight: 500;
    color: #1e293b;
}

.detail-value.na {
    color: #94a3b8;
    font-style: italic;
    font-weight: 400;
}

.na-text {
    font-size: 0.85rem;
    color: #94a3b8;
    font-style: italic;
}
</style>
