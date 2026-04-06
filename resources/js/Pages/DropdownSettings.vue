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
                        <Menu activeTab="data_setting" />
                    </div>

                    <div class="breadcrumb-card">
                        <div class="bc-home-icon">
                            <v-icon icon="mdi-home" size="16" color="#fff" />
                        </div>
                        <span class="bc-sep">›</span>
                        <span class="bc-active">Setup Dropdowns</span>
                    </div>

                    <div class="settings-layout">
                      
                        <div class="settings-sidebar" style="min-width: 250px;">
                            <button v-for="field in dynamicFields" 
                                    :key="field.key" 
                                    class="sidebar-item" 
                                    :class="{ active: activeField === field.key }" 
                                    @click="activeField = field.key; resetForms()">
                                <v-icon :icon="field.icon" size="20" style="margin-right:8px;" />
                                {{ field.label }}
                            </button>
                        </div>

                       
                        <div class="settings-content" style="background:#fff; border-radius:12px; border:1px solid #e5e7eb; padding:32px;">
                            <div class="card-header pb-4 border-b">
                                <div>
                                    <h2 class="card-title">{{ currentFieldLabel }} Setup</h2>
                                    <p class="card-sub">Add or delete options to appear in the {{ currentFieldLabel }} dropdown.</p>
                                </div>
                            </div>

                            <div style="margin-top: 24px;">
                                
                                <form @submit.prevent="addOption" style="display:flex; gap:16px; align-items:flex-end; margin-bottom: 24px;">
                                    <div class="form-group" style="flex:1;">
                                        <label>New {{ currentFieldLabel }} Option</label>
                                        <input type="text" v-model="newOptionVal" class="form-input" placeholder="Enter new option value" required />
                                    </div>
                                    <button type="submit" class="btn-solid-green" style="height: 42px;" :disabled="isProcessing">
                                        <v-icon icon="mdi-plus" style="margin-right:4px;" size="16" /> Add
                                    </button>
                                </form>

                               
                                <div style="overflow-x: auto; width: 100%;">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Option Value</th>
                                                <th style="width:150px; text-align:right;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="opt in currentOptions" :key="opt.id">
                                                <td>
                                                    <div v-if="editingId === opt.id" style="display:flex; gap:8px;">
                                                        <input type="text" v-model="editingVal" class="form-input" style="padding:4px 8px; font-size:14px; height:32px;" />
                                                        <button @click="updateOption(opt.id)" style="color:green; font-weight:600; font-size:13px; background:transparent; border:none; cursor:pointer;">Save</button>
                                                        <button @click="editingId = null" style="color:gray; font-size:13px; background:transparent; border:none; cursor:pointer;">Cancel</button>
                                                    </div>
                                                    <span v-else>{{ opt.option_value }}</span>
                                                </td>
                                                <td class="action-td">
                                                    <div class="btn-wrap">
                                                        <div v-if="editingId === opt.id" style="display:flex; gap:8px;">
                                                            <button @click="updateOption(opt.id)" class="btn-save">Save</button>
                                                            <button @click="editingId = null" class="btn-cancel">Cancel</button>
                                                        </div>
                                                        <template v-else>
                                                            <button @click="startEdit(opt)" class="btn-edit">
                                                                <v-icon icon="mdi-pencil" size="12" /> Edit
                                                            </button>
                                                            <button @click="deleteOption(opt.id)" class="btn-delete">
                                                                <v-icon icon="mdi-delete" size="12" /> Delete
                                                            </button>
                                                        </template>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr v-if="!currentOptions.length">
                                                <td colspan="2" class="text-center" style="padding:20px; color:#9ca3af; font-style:italic;">No options configured for {{ currentFieldLabel }}.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
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
    options: { type: Array, default: () => [] }
});

const can = (permission) => {
    return auth.value?.permissions?.includes(permission);
};

const logout = () => router.post('/logout');

const dynamicFields = [
    { key: 'biz_type', label: 'Business Type', icon: 'mdi-briefcase-outline' },
    { key: 'source', label: 'Lead Source', icon: 'mdi-bullhorn-outline' },
    { key: 'division', label: 'Division', icon: 'mdi-map-marker-outline' },
    { key: 'township', label: 'Township', icon: 'mdi-home-city-outline' },
    { key: 'product', label: 'Product', icon: 'mdi-package-variant-closed' },
    { key: 'channel', label: 'Channel', icon: 'mdi-set-top-box' },
    { key: 'package', label: 'Package', icon: 'mdi-cube-outline' },
];

const activeField = ref('biz_type');
const currentFieldLabel = computed(() => dynamicFields.find(f => f.key === activeField.value)?.label);

const currentOptions = computed(() => {
    return props.options.filter(o => o.field_name === activeField.value);
});

const newOptionVal = ref('');
const isProcessing = ref(false);

const editingId = ref(null);
const editingVal = ref('');

const resetForms = () => {
    newOptionVal.value = '';
    editingId.value = null;
    editingVal.value = '';
};

const addOption = () => {
    if (!newOptionVal.value.trim()) return;
    isProcessing.value = true;
    router.post('/settings/tenant-fields', {
        field_name: activeField.value,
        option_value: newOptionVal.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            newOptionVal.value = '';
            isProcessing.value = false;
            Swal.fire({
                icon: 'success',
                title: 'Option Added!',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        },
        onError: () => {
            isProcessing.value = false;
        }
    });
};

const startEdit = (opt) => {
    editingId.value = opt.id;
    editingVal.value = opt.option_value;
};

const updateOption = (id) => {
    if (!editingVal.value.trim()) return;
    router.put(`/settings/tenant-fields/${id}`, {
        option_value: editingVal.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            editingId.value = null;
            Swal.fire({
                icon: 'success',
                title: 'Option Updated!',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        }
    });
};

const deleteOption = (id) => {
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
            router.delete(`/settings/tenant-fields/${id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        title: 'Deleted!',
                        text: 'Option removed successfully.',
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
@import '../../css/dropdownsettings.css';
</style>
