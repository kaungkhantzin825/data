<template>
    <div class="dash-widget" style="margin-bottom: 30px;">
        <div class="dw-header">
            <div class="dw-title" style="color:#2ecc71; font-size: 1.1rem; font-weight: 700;">{{ plan }} Customer</div>
            <div class="dw-toggle-pill">
                <span :class="{active: period === 'Monthly'}" @click="period = 'Monthly'">Monthly</span>
                <span :class="{active: period === 'Quarterly'}" @click="period = 'Quarterly'">Quarterly</span>
                <span :class="{active: period === 'Yearly'}" @click="period = 'Yearly'">Yearly</span>
            </div>
        </div>
        
        <div class="dw-stats">
            <div class="dws-box">
                <div class="dws-icon"><v-icon icon="mdi-account-outline" size="20" color="#fff"/></div>
                <div class="dws-val">{{ activeData.total_customers || 0 }}</div>
            </div>
            <div class="dws-box">
                <div class="dws-icon"><v-icon icon="mdi-currency-usd" size="20" color="#fff"/></div>
                <div class="dws-val">{{ Number(activeData.total_amount || 0).toLocaleString() }} Ks</div>
            </div>
        </div>

        <div class="dw-table-wrap">
            <table class="dw-table">
                <thead>
                    <tr>
                        <th>#</th><th>Business Name</th><th>Package</th><th>Plan</th><th>Amount</th><th>Sale Person<br><small style="color:#9ca3af;font-size:0.65rem;text-transform:none;">Created By</small></th>
                    </tr>
                </thead>
                <tbody>
                    <template v-if="activeData.recent_leads?.length">
                        <tr v-for="(log, i) in activeData.recent_leads" :key="log.id">
                            <td>{{ i+1 }}.</td>
                            <td>{{ log.business_name }}</td>
                            <td>{{ log.package }}</td>
                            <td>{{ log.plan }}</td>
                            <td style="font-weight:700;">{{ Number(log.amount).toLocaleString() }}</td>
                            <td style="color:#6b7280;">{{ log.creator_name }}</td>
                        </tr>
                    </template>
                    <tr v-else>
                        <td colspan="6" class="text-center" style="padding:20px; color:#9ca3af;font-style:italic;">No active projects found.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="dw-footer">
            <div class="dw-f-text">Showing 1 to {{ activeData.recent_leads?.length || 0 }} of {{ activeData.recent_leads?.length || 0 }} entries</div>
            <div class="dw-pagination">
                <button>&lt;</button>
                <button class="active">1</button>
                <button>&gt;</button>
            </div>
        </div>

        <div class="dw-header" style="margin-top: 40px; border-top: 1px solid #f3f4f6; padding-top: 20px;">
            <div class="dw-title" style="color:#2ecc71; font-weight: 600;">Sale Person for {{ plan }} Customer</div>
        </div>
        <div class="dw-table-wrap">
            <table class="dw-table">
                <thead>
                    <tr>
                        <th style="padding-left:16px;">Name</th><th class="text-center">No of link</th><th class="text-right" style="padding-right:16px;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <template v-if="activeData.sales_persons?.length">
                        <tr v-for="sp in activeData.sales_persons" :key="sp.name">
                            <td style="padding-left:16px; color:#6b7280;">{{ sp.name }}</td>
                            <td class="text-center">{{ sp.links }}</td>
                            <td class="text-right" style="padding-right:16px; font-weight:700;">{{ Number(sp.amount).toLocaleString() }}</td>
                        </tr>
                    </template>
                    <tr v-else>
                        <td colspan="3" class="text-center" style="padding:20px; color:#9ca3af;font-style:italic;">No sales data available.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="dw-footer">
            <div class="dw-f-text">Showing 1 to {{ activeData.sales_persons?.length || 0 }} of {{ activeData.sales_persons?.length || 0 }} entries</div>
            <div class="dw-pagination">
                <button>&lt;</button>
                <button class="active">1</button>
                <button>&gt;</button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    plan: { type: String, required: true },
    report: { type: Object, default: () => ({}) }
});

const period = ref('Monthly');

const activeData = computed(() => {
    if (!props.report) return {};
    return props.report[period.value] || {};
});
</script>
