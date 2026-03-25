<template>
    <v-app theme="light">
        <div class="dash-root">
            <header class="topbar">
                <div class="topbar-left">
                    <img src="/images/MOJOERESTO_ASSET_LOGO_BLACK800-2 1.png"
                         alt="Pipeline" class="nav-logo" />
                </div>
                <div class="topbar-right">
                    <div class="admin-menu" @click="adminOpen = !adminOpen">
                        <img v-if="auth?.profile_logo" :src="auth.profile_logo" class="profile-avatar-small" alt="Avatar"/>
                        <span class="admin-name">{{ auth?.name ?? 'admin' }}</span>
                        <v-icon :icon="adminOpen ? 'mdi-chevron-up' : 'mdi-chevron-down'"
                            size="18" color="#374151" />
                        <div v-if="adminOpen" class="admin-dropdown" @click.stop>
                            <div class="dd-item" @click="logout">
                                <v-icon icon="mdi-logout" size="16" />
                                Logout
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <div class="page-background-wrapper">
                <div class="bg-overlay"></div>
                
                <div class="page-content-wrapper">
                    <div class="banner-content">
                        <div class="module-badge">Lead Management</div>
                        
                        <Menu :activeTab="tab" />
                    </div>

                  
                    <div class="breadcrumb-card">
                        <div class="bc-home-icon">
                            <v-icon icon="mdi-home" size="16" color="#fff" />
                        </div>
                        <span class="bc-sep">›</span>
                        <span class="bc-text">Lead Management</span>
                        <span class="bc-sep">›</span>
                        <span v-if="tab === 'dashboard'" class="bc-active">Dashboard</span>
                        <template v-else>
                            <span :class="(tab === 'upload' || tab === 'create') ? 'bc-text' : 'bc-active'" style="text-transform: capitalize; cursor: pointer" @click="goToTab('lists')">Lists</span>
                            <span v-if="tab === 'upload'" class="bc-sep">›</span>
                            <span v-if="tab === 'upload'" class="bc-active">Upload Lead</span>
                            <span v-if="tab === 'create'" class="bc-sep">›</span>
                            <span v-if="tab === 'create'" class="bc-active">{{ editingLeadId ? 'Edit Lead' : 'Create Lead' }}</span>
                        </template>
                    </div>

                    <div class="dash-master-wrapper">
                        <!-- Dashboard Reports -->
                        <div v-if="tab === 'dashboard'" class="dash-col">
                            <DashboardHeader 
                                v-model:plan="dashSelectedPlan" 
                                v-model:month="dashMonth" 
                                v-model:year="dashYear" 
                                @update="updateDash"
                            />

                            <!-- Summary / Sales Reports (Left Column logic) -->
                            <template v-for="plan in leftPlans" :key="'summary-' + plan">
                                <CustomerSummaryWidget :plan="plan" :report="summaryReports[plan]" />
                            </template>

                            <!-- Project / DIA Reports (Right Column logic) -->
                            <template v-for="plan in rightPlans" :key="'dia-' + plan">
                                <ProjectStatusWidget :plan="plan" :report="diaReports[plan]" />
                            </template>
                        </div>

                        <!-- Lead Listing -->
                        <LeadList 
                            v-if="tab === 'lists'" 
                            :leads="leads" 
                            :filters="f" 
                            :availablePlans="availablePlans"
                            :availableBizTypes="availableBizTypes"
                            @upload="goToTab('upload')"
                            @download="downloadCsv"
                            @apply="applyFilters"
                            @reset="resetFilters"
                            @edit="editLead"
                            @page="goPage"
                        />

                        <!-- Lead Creation / Editing -->
                        <LeadForm 
                            v-if="tab === 'create'" 
                            :form="leadForm" 
                            :fieldOptions="fieldOptions"
                            :editingId="editingLeadId"
                            @submit="submitLead"
                            @cancel="resetLeadForm"
                        />

                        <!-- Bulk Import -->
                        <ImportWidget 
                            v-if="tab === 'upload'" 
                            @download="downloadCsv"
                            @create="resetLeadForm(); goToTab('create')"
                            @cancel="goToTab('lists')"
                            @upload="hUpload"
                        />
                    </div>
                </div>
            </div>
        </div>
    </v-app>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { router, usePage, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import Menu from '@/Components/Menu.vue';

// Modular Components
import DashboardHeader from '@/Components/Dashboard/DashboardHeader.vue';
import CustomerSummaryWidget from '@/Components/Dashboard/CustomerSummaryWidget.vue';
import ProjectStatusWidget from '@/Components/Dashboard/ProjectStatusWidget.vue';
import LeadList from '@/Components/Dashboard/LeadList.vue';
import LeadForm from '@/Components/Dashboard/LeadForm.vue';
import ImportWidget from '@/Components/Dashboard/ImportWidget.vue';

const props = defineProps({
    leads:   { type: Object, default: () => ({ data: [], from: 0, to: 0, total: 0, current_page: 1, last_page: 1 }) },
    filters: { type: Object, default: () => ({}) },
    activeTab: { type: String, default: 'dashboard' },
    summaryReports: { type: Object, default: () => ({}) },
    diaReports: { type: Object, default: () => ({}) },
    fieldOptions: { type: Object, default: () => ({}) },
    availablePlans: { type: Array, default: () => [] },
    availableBizTypes: { type: Array, default: () => [] }
});

const page       = usePage();
const auth       = computed(() => page.props.auth?.user);
const tab        = computed(() => props.activeTab || 'dashboard');
const adminOpen  = ref(false);

const dashSelectedPlan = ref('Enterprise Customer');
const dashMonth        = ref(new Date().getMonth() + 1);
const dashYear         = ref(new Date().getFullYear());

const updateDash = () => {
    router.get('/dashboard', { month: dashMonth.value, year: dashYear.value }, { preserveState: true, preserveScroll: true });
};

const leftPlans = computed(() => {
    const all = ['Enterprise', 'Business DIA', 'MojoeElite', 'Premium Home Fiber', 'Staff Plan'];
    let sel = (dashSelectedPlan.value || '').trim();
    if (sel === 'All Plans') return all;
    for (let a of all) {
        if (sel.includes(a) && sel.toLowerCase().includes('customer')) return [a];
    }
    return [];
});

const rightPlans = computed(() => {
    const all = ['Enterprise DIA', 'Business DIA', 'MojoeElite'];
    let sel = (dashSelectedPlan.value || '').trim();
    if (sel === 'All Plans') return all;
    return all.includes(sel) ? [sel] : [];
});

const dashPeriod = ref({});
const getPeriod = (plan) => dashPeriod.value[plan] || 'Monthly';
const setPeriod = (plan, p) => dashPeriod.value[plan] = p;

const getSummaryData = (plan) => {
    if (!props.summaryReports || !props.summaryReports[plan]) return {};
    const period = getPeriod(plan);
    return props.summaryReports[plan][period] || {};
};


const can = (permission) => {
    return auth.value?.is_admin || auth.value?.permissions?.includes(permission);
};

const checkAccess = () => {
    if (tab.value === 'dashboard' && !can('view_dashboard')) {
        if (can('view_leads') || can('action_upload_lead') || can('action_download_csv')) goToTab('lists');
        else if (can('section_lead_detail') || can('section_product') || can('section_other_information')) goToTab('create');
        else if (can('view_users') || can('manage_roles') || can('create_users') || can('edit_users') || can('delete_users')) router.get('/users');
        else if (can('manage_settings')) router.get('/settings');
    }
    if (tab.value === 'lists' && !can('view_leads') && !can('action_upload_lead') && !can('action_download_csv')) {
        goToTab('dashboard'); 
    }
};

onMounted(() => {
    checkAccess();
});

watch(tab, () => {
    checkAccess();
});

const goToTab = (t) => {
    const urls = {
        'dashboard': '/dashboard',
        'lists': '/leads',
        'create': '/leads/create',
        'upload': '/leads/upload'
    };
    if (urls[t]) {
        router.get(urls[t], {}, { preserveState: true, preserveScroll: true });
    }
};

const s1Hide = ref(false);
const s2Hide = ref(false);
const s3Hide = ref(false);

const editingLeadId = ref(null);

const leadForm = useForm({
    business_name: '', first_name: '', last_name: '', contact_email: '', phone: '', secondary_contact_number: '',
    biz_type: '', source: '', division: '', township: '', address: '',
    product: '', package: '', package_total: null, discount: null, note: '',
    status: '', channel: '', installation_appointment: '', est_contract_date: '', est_start_date: '',
    est_follow_up_date: '', is_referral: false, meeting_note: '', next_step: ''
});

const submitLead = () => {
    if (editingLeadId.value) {
        leadForm.put(`/leads/${editingLeadId.value}`, {
            preserveScroll: true,
            onSuccess: () => {
                Swal.fire('Success', 'Lead updated successfully', 'success');
                editingLeadId.value = null;
                leadForm.reset();
            }
        });
    } else {
        leadForm.post('/leads', {
            preserveScroll: true,
            onSuccess: () => {
                Swal.fire('Success', 'Lead created successfully', 'success');
                leadForm.reset();
            }
        });
    }
};

const editLead = (lead) => {
    editingLeadId.value = lead.uuid || lead.id;
    leadForm.business_name = lead.business_name || '';
    leadForm.first_name = lead.first_name || '';
    leadForm.last_name = lead.last_name || '';
    leadForm.contact_email = lead.contact_email || '';
    leadForm.phone = lead.phone || '';
    leadForm.secondary_contact_number = lead.secondary_contact_number || '';
    leadForm.biz_type = lead.biz_type || '';
    leadForm.source = lead.source || '';
    leadForm.division = lead.division || '';
    leadForm.township = lead.township || '';
    leadForm.address = lead.address || '';
    leadForm.product = lead.product || '';
    leadForm.package = lead.package || '';
    leadForm.package_total = lead.package_total || lead.amount || null;
    leadForm.discount = lead.discount || null;
    leadForm.note = lead.note || '';
    leadForm.status = lead.status || '';
    leadForm.channel = lead.channel || '';
    leadForm.installation_appointment = lead.installation_appointment || '';
    leadForm.est_contract_date = lead.est_contract_date || '';
    leadForm.est_start_date = lead.est_start_date || '';
    leadForm.est_follow_up_date = lead.est_follow_up_date || '';
    leadForm.is_referral = lead.is_referral ? true : false;
    leadForm.meeting_note = lead.meeting_note || '';
    leadForm.next_step = lead.next_step || '';
    goToTab('create');
};

const resetLeadForm = () => { 
    editingLeadId.value = null;
    leadForm.reset(); 
    goToTab('lists');
};

const f = ref({
    search:   props.filters?.search   ?? '',
    plan:     props.filters?.plan     ?? '',
    biz_type: props.filters?.biz_type ?? '',
    status:   props.filters?.status   ?? '',
});

const applyFilters = () => {
        router.get('/leads', f.value, { preserveState: true, preserveScroll: true, replace: true, only: ['leads'] });
};

const updateExisting = ref(false);

const downloadCsv = () => {
    const query = new URLSearchParams(f.value).toString();
    window.location.href = `/leads/export?${query}`;
};

const handleFileUpload = (e) => {
    const file = e.target.files[0];
    if (file) {
        uploadFile.value = file;
        uploadFileName.value = file.name;
    }
};

const hUpload = ({ file, updateExisting }) => {
    if (!file) {
        Swal.fire('Error', 'Please select a file to upload.', 'error');
        return;
    }
    const formData = new FormData();
    formData.append('file', file);
    formData.append('update_existing', updateExisting ? 'true' : 'false');
    router.post('/leads/import', formData, {
        preserveScroll: true,
        forceFormData: true,
        onStart: () => {
            Swal.fire({
                title: 'Uploading...',
                text: 'Uploading and parsing your data. Please wait.',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
        },
        onSuccess: (page) => {
            const resultMsg = page.props.flash?.success || 'File uploaded and leads imported successfully!';
            Swal.fire('Success', resultMsg, 'success');
        },
        onError: (err) => {
            console.error(err);
            Swal.fire('Error', 'There was an error uploading the CSV file. Please check your columns.', 'error');
        }
    });
};

const resetFilters = () => {
    f.value = { search: '', plan: '', biz_type: '', status: '' };
    router.get('/leads', {}, { preserveState: true, preserveScroll: true, only: ['leads'] });
};

const goPage = (p) => {
    router.get('/leads', { ...f.value, page: p }, { preserveState: true, preserveScroll: true, only: ['leads'] });
};

const goToUsers = () => {
    router.get('/users');
};

const logout = () => router.post('/logout');

const pagPages = computed(() => {
    const last = props.leads?.last_page ?? 1;
    return Array.from({ length: Math.min(last, 10) }, (_, i) => i + 1);
});
</script>

<style>
@import '../../css/dashboard.css';
</style>
