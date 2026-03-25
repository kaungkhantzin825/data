<template>
    <div class="dash-widget" style="margin-bottom: 30px;">
        <div class="dw-header">
            <div class="dw-title" style="color:#2ecc71; font-size: 1.1rem; font-weight: 700;">{{ plan }}</div>
            <div class="dw-select-action">
                <select><option>50%</option><option>100%</option></select>
            </div>
        </div>
        
        <div class="dw-stats">
            <div class="dws-box">
                <div class="dws-icon"><v-icon icon="mdi-account-outline" size="20" color="#fff"/></div>
                <div class="dws-val">{{ report.total_customers || 0 }}</div>
            </div>
            <div class="dws-box">
                <div class="dws-icon"><v-icon icon="mdi-currency-usd" size="20" color="#fff"/></div>
                <div class="dws-val">{{ Number(report.total_amount || 0).toLocaleString() }} Ks</div>
            </div>
        </div>

        <div class="dw-table-wrap">
            <table class="dw-table">
                <thead>
                    <tr>
                        <th style="padding-left:16px;">#</th><th>Business Name</th><th>Package</th><th>Plan</th><th class="text-right" style="padding-right:16px;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <template v-if="report.recent_leads?.length">
                        <tr v-for="(log, i) in report.recent_leads" :key="log.id">
                            <td style="padding-left:16px;">{{ i+1 }}.</td>
                            <td>{{ log.business_name }}</td>
                            <td>{{ log.package }}</td>
                            <td>{{ log.plan }}</td>
                            <td class="text-right" style="padding-right:16px; font-weight:700;">{{ Number(log.amount).toLocaleString() }}</td>
                        </tr>
                    </template>
                    <tr v-else>
                        <td colspan="5" class="text-center" style="padding:20px; color:#9ca3af;font-style:italic;">No active projects found.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="dw-footer">
            <div class="dw-f-text">Showing 1 to {{ report.recent_leads?.length || 0 }} of {{ report.recent_leads?.length || 0 }} entries</div>
            <div class="dw-pagination">
                <button>&lt;</button>
                <button class="active">1</button>
                <button>&gt;</button>
            </div>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    plan: { type: String, required: true },
    report: { type: Object, default: () => ({}) }
});
</script>
