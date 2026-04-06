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
                        <Menu activeTab="plans" />
                    </div>

                    <div class="breadcrumb-card">
                        <div class="bc-home-icon">
                            <v-icon icon="mdi-home" size="16" color="#fff" />
                        </div>
                        <span class="bc-sep">›</span>
                        <span class="bc-active">SaaS Plans</span>
                    </div>

                    <div class="content-card" style="padding: 32px;">
                         <div class="card-header pb-4 border-b">
                             <div>
                                 <h2 class="card-title">SaaS Plans Management</h2>
                                 <p class="card-sub">Manage subscription plans, staff limits, and durations for tenant assignment.</p>
                             </div>
                         </div>
                         <div style="margin-top: 24px;" class="custom-fields-wrapper">
                             <form @submit.prevent="savePlan">
                                 <div class="form-grid-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 16px;">
                                     <div class="form-group">
                                         <label>Plan Name</label>
                                         <input type="text" v-model="planForm.name" class="form-input" required />
                                     </div>
                                     <div class="form-group">
                                         <label>Staff Limit</label>
                                         <input type="number" v-model="planForm.staff_limit" class="form-input" min="1" required />
                                     </div>
                                     <div class="form-group">
                                         <label>Duration (Days)</label>
                                         <input type="number" v-model="planForm.duration_in_days" class="form-input" min="1" required />
                                     </div>
                                     <div class="form-group" style="grid-column: span 2; display: flex; flex-direction: row; justify-content: flex-end; margin-top: 8px;">
                                         <button type="submit" class="btn-solid-green" style="width: max-content;">Create Plan</button>
                                     </div>
                                 </div>
                             </form>

                             <div style="overflow-x: auto; width: 100%;">
                                 <table class="data-table" style="margin-top: 20px;">
                                     <thead>
                                         <tr>
                                             <th>Name</th>
                                             <th>Staff Limit</th>
                                             <th>Duration (Days)</th>
                                             <th>Actions</th>
                                         </tr>
                                     </thead>
                                     <tbody>
                                         <tr v-for="plan in plans" :key="plan.id">
                                             <td>
                                                 <input v-if="editingPlanId === plan.id" type="text" v-model="editPlanForm.name" class="form-input" style="padding:4px 8px; font-size:14px; height:32px;" />
                                                 <span v-else>{{ plan.name }}</span>
                                             </td>
                                             <td>
                                                 <input v-if="editingPlanId === plan.id" type="number" v-model="editPlanForm.staff_limit" class="form-input" style="padding:4px 8px; font-size:14px; height:32px; width:80px;" min="1" />
                                                 <span v-else>{{ plan.staff_limit }}</span>
                                             </td>
                                             <td>
                                                 <input v-if="editingPlanId === plan.id" type="number" v-model="editPlanForm.duration_in_days" class="form-input" style="padding:4px 8px; font-size:14px; height:32px; width:100px;" min="1" required />
                                                 <span v-else>{{ plan.duration_in_days }} days</span>
                                             </td>
                                             <td class="action-td">
                                                 <div class="btn-wrap">
                                                     <template v-if="editingPlanId === plan.id">
                                                         <button @click="updatePlan(plan.id)" class="btn-save">Save</button>
                                                         <button @click="editingPlanId = null" class="btn-cancel">Cancel</button>
                                                     </template>
                                                     <template v-else>
                                                         <button @click="startEdit(plan)" class="btn-edit">
                                                             <v-icon icon="mdi-pencil" size="12" /> Edit
                                                         </button>
                                                         <button @click="deletePlan(plan.id)" class="btn-delete">
                                                             <v-icon icon="mdi-delete" size="12" /> Delete
                                                         </button>
                                                     </template>
                                                 </div>
                                             </td>
                                         </tr>
                                         <tr v-if="!plans || plans.length === 0">
                                            <td colspan="4" class="text-center" style="padding: 16px;">No plans created yet.</td>
                                         </tr>
                                     </tbody>
                                 </table>
                             </div>
                         </div>
                    </div>

                </div>
            </div>
            
        </div>
    </v-app>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, usePage, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import Menu from '@/Components/Menu.vue';

const page = usePage();
const auth = computed(() => page.props.auth?.user);
const adminOpen = ref(false);

const props = defineProps({
    plans: { type: Array, default: () => [] }
});

const can = (permission) => {
    return auth.value?.permissions?.includes(permission);
};

const logout = () => router.post('/logout');

const planForm = useForm({
    name: '',
    staff_limit: 1,
    duration_in_days: 30,
    description: ''
});

const savePlan = () => {
    planForm.post('/plans', {
        preserveScroll: true,
        onSuccess: () => {
            planForm.reset();
            Swal.fire({
                icon: 'success',
                title: 'Plan Created!',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        }
    });
};

const editingPlanId = ref(null);
const editPlanForm = useForm({
    name: '',
    staff_limit: 1,
    duration_in_days: 30
});

const startEdit = (plan) => {
    editingPlanId.value = plan.id;
    editPlanForm.name = plan.name;
    editPlanForm.staff_limit = plan.staff_limit;
    editPlanForm.duration_in_days = plan.duration_in_days;
};

const updatePlan = (id) => {
    editPlanForm.put(`/plans/${id}`, {
        preserveScroll: true,
        onSuccess: () => {
            editingPlanId.value = null;
            Swal.fire({
                icon: 'success',
                title: 'Plan Updated!',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        }
    });
};

const deletePlan = (id) => {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#2ecc5e',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/plans/${id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        title: 'Deleted!',
                        text: 'Plan has been deleted.',
                        icon: 'success',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    });
                }
            });
        }
    });
};
</script>

<style scoped>
@import '../../css/planmanagement.css';
</style>