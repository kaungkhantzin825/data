<template>
    <div class="subnav-container">
        <div class="subnav">
            <button class="subnav-btn" :class="{ 'active': activeTab === 'dashboard' }" v-if="can('view_dashboard')" @click="router.get('/dashboard')">Dashboard</button>
            <button class="subnav-btn" :class="{ 'active': activeTab === 'lists' }" v-if="can('view_leads') || can('action_upload_lead') || can('action_download_csv')" @click="router.get('/leads')">Lists</button>
            <button class="subnav-btn" :class="{ 'active': activeTab === 'create' }" v-if="can('section_lead_detail') || can('section_product') || can('section_other_information')" @click="router.get('/leads/create')">Create</button>
            <button class="subnav-btn" :class="{ 'active': activeTab === 'users' }" v-if="can('view_users') || can('create_users') || can('edit_users') || can('delete_users') || can('manage_roles')" @click="router.get('/users')">User Management</button>
            <button class="subnav-btn" :class="{ 'active': activeTab === 'plans' }" v-if="can('manage_plans')" @click="router.get('/plans')">Plan Menu</button>
            <button class="subnav-btn" :class="{ 'active': activeTab === 'data_setting' }" v-if="can('manage_tenant_fields')" @click="router.get('/settings/tenant-fields')">Data setting</button>
            <button class="subnav-btn" :class="{ 'active': activeTab === 'setting' }" v-if="can('manage_settings')" @click="router.get('/settings')">Setting</button>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

const props = defineProps({
    activeTab: {
        type: String,
        default: ''
    }
});

const page = usePage();
const auth = computed(() => page.props.auth?.user);

const can = (permission) => {
    return auth.value?.permissions?.includes(permission);
};
</script>

<style scoped>
.subnav-container { width: 100%; border-bottom: 1px solid rgba(255, 255, 255, 0.3); }
.subnav { display: flex; gap: 8px; overflow-x: auto; padding-bottom: 0px; }
.subnav::-webkit-scrollbar { display: none; }
.subnav-btn {
    background: transparent; border: none; color: #e5e7eb; font-size: 0.95rem; font-weight: 500;
    padding: 10px 16px; cursor: pointer; transition: all 0.2s; white-space: nowrap; border-bottom: 2px solid transparent;
}
.subnav-btn:hover { color: #fff; background: rgba(255, 255, 255, 0.1); border-radius: 6px 6px 0 0; }
.subnav-btn.active { color: #fff; font-weight: 600; border-bottom: 2px solid #2ecc5e; }
</style>
