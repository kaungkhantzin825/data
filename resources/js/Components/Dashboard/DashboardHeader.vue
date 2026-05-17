<template>
    <div class="dash-title-card">
        <div class="dtc-left">
            <div class="dtc-h2">Lead Management - Dashboard</div>
            <div class="dtc-sub">Overview of lead performance and status</div>
        </div>
        <div class="dtc-right">
            <div v-if="auth?.role === 'Company Super Admin' || auth?.is_admin" class="select-wrap-dash" style="min-width: 170px;">
                <select class="dash-select" v-model="selectedUser" @change="$emit('update')">
                    <option value="">All Users</option>
                    <option v-for="user in availableUsers" :key="user.id" :value="user.id">{{ user.name }}</option>
                </select>
            </div>
            <div class="select-wrap-dash" style="min-width: 170px;">
                <select class="dash-select" v-model="selectedPlan">
                    <option value="All Plans">All Plans</option>
                    <option v-for="plan in planOptions" :key="plan" :value="plan">{{ plan }}</option>
                </select>
            </div>
            <div class="select-wrap-dash">
                <select class="dash-select" v-model="selectedMonth" @change="$emit('update')">
                    <option v-for="(m, i) in monthOptions" :key="m" :value="i+1">{{ m }}</option>
                </select>
            </div>
            <div class="select-wrap-dash">
                <select class="dash-select" v-model="selectedYear" @change="$emit('update')">
                    <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
                </select>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const auth = computed(() => page.props.auth?.user);

const selectedPlan  = defineModel('plan');
const selectedMonth = defineModel('month');
const selectedYear  = defineModel('year');
const selectedUser  = defineModel('user');

defineProps({
    availableUsers: { type: Array, default: () => [] }
});

defineEmits(['update']);

const planOptions = [
    'Enterprise Customer', 'Enterprise DIA', 'Business DIA', 'Business DIA Customer', 
    'MojoeElite', 'MojoeElite Customer', 'Premium Home Fiber Customer', 'Staff Plan Customer'
];

const monthOptions = [
    'January','February','March','April','May','June',
    'July','August','September','October','November','December'
];

const yearOptions = [2024, 2025, 2026, 2027];
</script>
