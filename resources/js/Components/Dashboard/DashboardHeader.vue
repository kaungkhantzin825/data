<template>
    <div class="dash-filter-card">
        <!-- Title row -->
        <div class="dfc-title-row">
            <div class="dfc-title-left">
                <div class="dfc-icon-wrap">
                    <v-icon icon="mdi-chart-line" size="20" color="#2ecc5e" />
                </div>
                <div>
                    <div class="dfc-h2">Lead Dashboard</div>
                    <div class="dfc-sub">Filter and analyze your lead performance</div>
                </div>
            </div>
            <!-- Month / Year calendar pills -->
            <div class="dfc-calendar-row">
                <v-icon icon="mdi-calendar-month" size="16" color="#6b7280" style="margin-right:6px;" />
                <select class="dfc-select dfc-select-sm" v-model="localMonth" @change="emitUpdate">
                    <option v-for="(m, i) in monthOptions" :key="m" :value="i + 1">{{ m }}</option>
                </select>
                <select class="dfc-select dfc-select-sm" v-model="localYear" @change="emitUpdate">
                    <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
                </select>
            </div>
        </div>

        <!-- Filter chips row -->
        <div class="dfc-filters-row">

            <!-- Plan -->
            <div class="dfc-filter-group">
                <label class="dfc-label">
                    <v-icon icon="mdi-tag-outline" size="13" /> Plan
                </label>
                <div class="dfc-select-wrap">
                    <select class="dfc-select" v-model="localPlan" @change="emitUpdate">
                        <option value="">All Plans</option>
                        <option v-for="p in availablePlans" :key="p" :value="p">{{ p }}</option>
                    </select>
                </div>
            </div>

            <!-- Business Type -->
            <div class="dfc-filter-group">
                <label class="dfc-label">
                    <v-icon icon="mdi-domain" size="13" /> Business Type
                </label>
                <div class="dfc-select-wrap">
                    <select class="dfc-select" v-model="localBizType" @change="emitUpdate">
                        <option value="">All Types</option>
                        <option v-for="b in availableBizTypes" :key="b" :value="b">{{ b }}</option>
                    </select>
                </div>
            </div>

            <!-- Status -->
            <div class="dfc-filter-group">
                <label class="dfc-label">
                    <v-icon icon="mdi-circle-outline" size="13" /> Status
                </label>
                <div class="dfc-select-wrap">
                    <select class="dfc-select" v-model="localStatus" @change="emitUpdate">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>
            </div>

            <!-- ── SUPER ADMIN: Manager → Org → Staff ── -->
            <template v-if="isSuperAdmin">
                <div class="dfc-divider" />
                <div class="dfc-filter-group">
                    <label class="dfc-label">
                        <v-icon icon="mdi-account-tie" size="13" /> Manager
                    </label>
                    <div class="dfc-select-wrap">
                        <select class="dfc-select" v-model="localManagerId" @change="onManagerChange">
                            <option value="">All Managers</option>
                            <option v-for="m in superAdminData?.managers" :key="m.id" :value="m.id">{{ m.name }}</option>
                        </select>
                    </div>
                </div>
                <div class="dfc-filter-group" v-if="saFilteredOrgs.length">
                    <label class="dfc-label">
                        <v-icon icon="mdi-office-building-outline" size="13" /> Organization
                    </label>
                    <div class="dfc-select-wrap">
                        <select class="dfc-select" v-model="localOrgId" @change="onOrgChange">
                            <option value="">All Organizations</option>
                            <option v-for="org in saFilteredOrgs" :key="org.id" :value="org.id">{{ org.name }}</option>
                        </select>
                    </div>
                </div>
                <div class="dfc-filter-group" v-if="saFilteredStaff.length">
                    <label class="dfc-label">
                        <v-icon icon="mdi-account-outline" size="13" /> Staff
                    </label>
                    <div class="dfc-select-wrap">
                        <select class="dfc-select" v-model="localStaffId" @change="emitUpdate">
                            <option value="">All Staff</option>
                            <option v-for="s in saFilteredStaff" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                    </div>
                </div>
            </template>

            <!-- ── MANAGER: Org → Staff ── -->
            <template v-else-if="isManager">
                <div class="dfc-divider" />
                <div class="dfc-filter-group" v-if="managerOrgs?.length">
                    <label class="dfc-label">
                        <v-icon icon="mdi-office-building-outline" size="13" /> Organization
                    </label>
                    <div class="dfc-select-wrap">
                        <select class="dfc-select" v-model="localOrgId" @change="onOrgChange">
                            <option value="">All Organizations</option>
                            <option v-for="org in managerOrgs" :key="org.id" :value="org.id">{{ org.name }}</option>
                        </select>
                    </div>
                </div>
                <div class="dfc-filter-group" v-if="filteredStaffList.length">
                    <label class="dfc-label">
                        <v-icon icon="mdi-account-outline" size="13" /> Staff
                    </label>
                    <div class="dfc-select-wrap">
                        <select class="dfc-select" v-model="localStaffId" @change="emitUpdate">
                            <option value="">All Staff</option>
                            <option v-for="s in filteredStaffList" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                    </div>
                </div>
            </template>

            <!-- Search button -->
            <button class="dfc-search-btn" @click="emitUpdate">
                <v-icon icon="mdi-magnify" size="15" />
                Search
            </button>
            <button class="dfc-reset-btn" @click="resetFilters" v-if="hasActiveFilters">
                <v-icon icon="mdi-close" size="13" />
                Reset
            </button>
        </div>

        <!-- Active filter badges -->
        <div class="dfc-badges" v-if="hasActiveFilters">
            <span v-if="localPlan"      class="dfc-badge">Plan: {{ localPlan }} <span @click="localPlan = ''; emitUpdate()">✕</span></span>
            <span v-if="localBizType"   class="dfc-badge">Type: {{ localBizType }} <span @click="localBizType = ''; emitUpdate()">✕</span></span>
            <span v-if="localStatus"    class="dfc-badge">Status: {{ localStatus }} <span @click="localStatus = ''; emitUpdate()">✕</span></span>
            <span v-if="localManagerId" class="dfc-badge">Manager: {{ managerName }} <span @click="onManagerChange(); localManagerId = ''; emitUpdate()">✕</span></span>
            <span v-if="localOrgId"     class="dfc-badge">Org: {{ orgName }} <span @click="localOrgId = ''; localStaffId = ''; emitUpdate()">✕</span></span>
            <span v-if="localStaffId"   class="dfc-badge">Staff: {{ staffName }} <span @click="localStaffId = ''; emitUpdate()">✕</span></span>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const auth = computed(() => page.props.auth?.user);
const isSuperAdmin = computed(() => auth.value?.role === 'Company Super Admin');
const isManager    = computed(() => auth.value?.role === 'Manager');

const props = defineProps({
    availablePlans:    { type: Array,  default: () => [] },
    availableBizTypes: { type: Array,  default: () => [] },
    superAdminData:    { type: Object, default: () => ({ managers: [], orgs: [], staff: [] }) },
    managerOrgs:       { type: Array,  default: () => [] },
    staffList:         { type: Array,  default: () => [] },
    filters:           { type: Object, default: () => ({}) },
    month:             { type: Number, default: () => new Date().getMonth() + 1 },
    year:              { type: Number, default: () => new Date().getFullYear() },
});

const emit = defineEmits(['update']);

// Local filter state
const localPlan      = ref(props.filters?.plan       ?? '');
const localBizType   = ref(props.filters?.biz_type   ?? '');
const localStatus    = ref(props.filters?.status     ?? '');
const localManagerId = ref(props.filters?.manager_id ?? '');
const localOrgId     = ref(props.filters?.org_id     ?? '');
const localStaffId   = ref(props.filters?.staff_id   ?? '');
const localMonth     = ref(props.month);
const localYear      = ref(props.year);

// Cascading computed for Super Admin
const saFilteredOrgs = computed(() => {
    if (!props.superAdminData?.orgs?.length) return [];
    if (!localManagerId.value) return props.superAdminData.orgs;
    return props.superAdminData.orgs.filter(o => String(o.manager_id) === String(localManagerId.value));
});

const saFilteredStaff = computed(() => {
    if (!props.superAdminData?.staff?.length) return [];
    let list = props.superAdminData.staff;
    if (localManagerId.value) list = list.filter(s => String(s.manager_id) === String(localManagerId.value));
    if (localOrgId.value)     list = list.filter(s => s.org_ids?.map(String).includes(String(localOrgId.value)));
    return list;
});

// Cascading computed for Manager
const filteredStaffList = computed(() => {
    if (!localOrgId.value) return props.staffList;
    return props.staffList.filter(s => s.org_ids?.map(String).includes(String(localOrgId.value)));
});

// Badge labels
const managerName = computed(() => props.superAdminData?.managers?.find(m => String(m.id) === String(localManagerId.value))?.name ?? '');
const orgName     = computed(() => {
    const allOrgs = [...(props.superAdminData?.orgs ?? []), ...(props.managerOrgs ?? [])];
    return allOrgs.find(o => String(o.id) === String(localOrgId.value))?.name ?? '';
});
const staffName   = computed(() => {
    const allStaff = [...(props.superAdminData?.staff ?? []), ...(props.staffList ?? [])];
    return allStaff.find(s => String(s.id) === String(localStaffId.value))?.name ?? '';
});

const hasActiveFilters = computed(() =>
    localPlan.value || localBizType.value || localStatus.value ||
    localManagerId.value || localOrgId.value || localStaffId.value
);

const onManagerChange = () => { localOrgId.value = ''; localStaffId.value = ''; emitUpdate(); };
const onOrgChange     = () => { localStaffId.value = ''; emitUpdate(); };

const emitUpdate = () => {
    emit('update', {
        plan:       localPlan.value,
        biz_type:   localBizType.value,
        status:     localStatus.value,
        manager_id: localManagerId.value,
        org_id:     localOrgId.value,
        staff_id:   localStaffId.value,
        month:      localMonth.value,
        year:       localYear.value,
    });
};

const resetFilters = () => {
    localPlan.value = ''; localBizType.value = ''; localStatus.value = '';
    localManagerId.value = ''; localOrgId.value = ''; localStaffId.value = '';
    emitUpdate();
};

const monthOptions = ['January','February','March','April','May','June','July','August','September','October','November','December'];
const yearOptions  = [2024, 2025, 2026, 2027];
</script>

<style scoped>
.dash-filter-card {
    background: #fff;
    border-radius: 14px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    padding: 18px 22px 14px;
    margin-bottom: 16px;
}
.dfc-title-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 14px;
    gap: 12px;
    flex-wrap: wrap;
}
.dfc-title-left { display: flex; align-items: center; gap: 10px; }
.dfc-icon-wrap {
    width: 38px; height: 38px; border-radius: 10px;
    background: linear-gradient(135deg, #d1fae5, #a7f3d0);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.dfc-h2 { font-size: 1rem; font-weight: 700; color: #111827; }
.dfc-sub { font-size: 0.78rem; color: #6b7280; margin-top: 1px; }

.dfc-calendar-row {
    display: flex; align-items: center; gap: 8px;
    background: #f9fafb; border: 1px solid #e5e7eb;
    border-radius: 10px; padding: 6px 12px;
}
.dfc-filters-row {
    display: flex; align-items: flex-end;
    gap: 10px; flex-wrap: wrap;
}
.dfc-filter-group { display: flex; flex-direction: column; gap: 4px; }
.dfc-label {
    font-size: 0.72rem; font-weight: 600; color: #6b7280;
    text-transform: uppercase; letter-spacing: 0.4px;
    display: flex; align-items: center; gap: 3px;
}
.dfc-select-wrap { position: relative; }
.dfc-select {
    appearance: none; -webkit-appearance: none;
    background: #f9fafb url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24'%3E%3Cpath fill='%236b7280' d='M7 10l5 5 5-5z'/%3E%3C/svg%3E") no-repeat right 8px center;
    border: 1px solid #e5e7eb; border-radius: 8px;
    padding: 7px 28px 7px 10px; font-size: 0.83rem;
    color: #374151; cursor: pointer; min-width: 130px;
    transition: border-color 0.15s, box-shadow 0.15s;
}
.dfc-select:focus { outline: none; border-color: #2ecc5e; box-shadow: 0 0 0 3px rgba(46,204,94,0.12); }
.dfc-select-sm { min-width: 0; padding: 5px 26px 5px 8px; font-size: 0.8rem; }
.dfc-divider { width: 1px; height: 36px; background: #e5e7eb; margin: 0 4px; align-self: flex-end; }
.dfc-search-btn {
    display: flex; align-items: center; gap: 5px;
    background: #2ecc5e; color: #fff;
    border: none; border-radius: 8px;
    padding: 8px 16px; font-size: 0.83rem; font-weight: 600;
    cursor: pointer; transition: background 0.15s;
    align-self: flex-end;
}
.dfc-search-btn:hover { background: #25b351; }
.dfc-reset-btn {
    display: flex; align-items: center; gap: 4px;
    background: #f3f4f6; color: #6b7280;
    border: 1px solid #e5e7eb; border-radius: 8px;
    padding: 8px 12px; font-size: 0.8rem; font-weight: 500;
    cursor: pointer; transition: all 0.15s;
    align-self: flex-end;
}
.dfc-reset-btn:hover { background: #fee2e2; color: #dc2626; border-color: #fca5a5; }
.dfc-badges { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 10px; }
.dfc-badge {
    display: flex; align-items: center; gap: 5px;
    background: #ecfdf5; color: #065f46;
    border: 1px solid #6ee7b7;
    border-radius: 20px; padding: 3px 10px;
    font-size: 0.75rem; font-weight: 500;
}
.dfc-badge span { cursor: pointer; opacity: 0.6; }
.dfc-badge span:hover { opacity: 1; color: #dc2626; }
</style>
