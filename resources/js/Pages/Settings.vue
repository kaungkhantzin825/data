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
                        <Menu activeTab="setting" />
                    </div>

                 
                    <div class="breadcrumb-card">
                        <div class="bc-home-icon">
                            <v-icon icon="mdi-home" size="16" color="#fff" />
                        </div>
                        <span class="bc-sep">›</span>
                        <span class="bc-active">Settings</span>
                    </div>

                  
                    <div class="settings-layout">
                        
                        <!-- Sidebar Menu -->
                        <div class="settings-sidebar">
                            <button class="sidebar-item" v-if="can('setting_profile')" :class="{ active: activeSettingTab === 'profile' }" @click="activeSettingTab = 'profile'">
                                <v-icon icon="mdi-account-circle-outline" size="20" style="margin-right:8px;" />
                                Profile Settings
                            </button>
                            <button class="sidebar-item" v-if="can('setting_backup')" :class="{ active: activeSettingTab === 'backup' }" @click="activeSettingTab = 'backup'">
                                <v-icon icon="mdi-database-outline" size="20" style="margin-right:8px;" />
                                Database Backup
                            </button>
                            <button class="sidebar-item" v-if="can('setting_activity')" :class="{ active: activeSettingTab === 'activity' }" @click="activeSettingTab = 'activity'">
                                <v-icon icon="mdi-history" size="20" style="margin-right:8px;" />
                                Activity Log
                            </button>
                            <button class="sidebar-item" v-if="can('setting_user_status')" :class="{ active: activeSettingTab === 'users' }" @click="activeSettingTab = 'users'">
                                <v-icon icon="mdi-account-check-outline" size="20" style="margin-right:8px;" />
                                User Login Status
                            </button>
                        </div>

                       
                        <div class="settings-content">
                            <div class="content-card" v-if="activeSettingTab === 'profile'" style="padding: 32px;">
                                <div class="card-header pb-4 border-b">
                                    <div>
                                        <h2 class="card-title">Profile Settings</h2>
                                        <p class="card-sub">Update your personal information, password, and profile logo.</p>
                                    </div>
                                </div>

                                <form @submit.prevent="saveSettings" style="margin-top: 24px;">
                                    <div class="form-group mb-4">
                                        <label>Profile Logo</label>
                                        <div class="avatar-row">
                                            <div class="avatar-preview" v-if="avatarPreview || auth?.profile_logo">
                                                <img :src="avatarPreview || auth?.profile_logo" alt="Profile Logo" />
                                            </div>
                                            <div class="avatar-placeholder" v-else>
                                                <v-icon icon="mdi-camera" size="24" color="#9ca3af" />
                                            </div>
                                            <input type="file" ref="fileInput" @change="handleFileChange" class="file-input-hidden" accept="image/*" />
                                            <button type="button" class="btn-outline-green" @click="$refs.fileInput.click()">
                                                <v-icon icon="mdi-upload" size="14" style="margin-right:4px;" /> Choose Photo
                                            </button>
                                        </div>
                                        <span v-if="settingsForm.errors.profile_logo" class="error-text">{{ settingsForm.errors.profile_logo }}</span>
                                    </div>

                                    <div class="form-group mb-4">
                                        <label>Name</label>
                                        <input v-model="settingsForm.name" type="text" class="form-input" placeholder="Your name" />
                                        <span v-if="settingsForm.errors.name" class="error-text">{{ settingsForm.errors.name }}</span>
                                    </div>

                                    <div class="form-group mb-4">
                                        <label>Email Address</label>
                                        <input v-model="settingsForm.email" type="email" class="form-input" placeholder="Your email address" />
                                        <span v-if="settingsForm.errors.email" class="error-text">{{ settingsForm.errors.email }}</span>
                                    </div>

                                    <div class="form-group mb-4">
                                        <label>New Password <span style="font-weight:400; color:#9ca3af; font-size:11px;">(Leave blank to keep current)</span></label>
                                        <input v-model="settingsForm.password" type="password" class="form-input" placeholder="Enter new password" />
                                        <span v-if="settingsForm.errors.password" class="error-text">{{ settingsForm.errors.password }}</span>
                                    </div>

                                    <div class="form-group mb-6">
                                        <label>Confirm Password</label>
                                        <input v-model="settingsForm.password_confirmation" type="password" class="form-input" placeholder="Confirm new password" />
                                    </div>

                                    <div class="flex justify-end pt-4 border-t">
                                        <button type="submit" class="btn-solid-green" :disabled="settingsForm.processing">
                                            {{ settingsForm.processing ? 'Saving...' : 'Save Settings' }}
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <div class="content-card" v-if="activeSettingTab === 'backup'" style="padding: 32px;">
                                <div class="card-header pb-4 border-b">
                                    <div>
                                        <h2 class="card-title">Database Backup</h2>
                                        <p class="card-sub">Generate and download full SQL backups of your database.</p>
                                    </div>
                                    <div>
                                        <button @click="generateBackup" class="btn-solid-green" :disabled="isGeneratingBackup">
                                            <v-icon icon="mdi-database-export" size="14" style="margin-right:6px;" /> 
                                            {{ isGeneratingBackup ? 'Generating...' : 'Generate Backup' }}
                                        </button>
                                    </div>
                                </div>
                                <div style="margin-top: 24px; overflow-x: auto; width: 100%;">
                                    <table v-if="backups && backups.length > 0" class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Backup File</th>
                                                <th>Size</th>
                                                <th>Date</th>
                                                <th class="text-right">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="backup in backups" :key="backup.id">
                                                <td style="font-weight: 500; color: #111827;">{{ backup.id }}</td>
                                                <td>{{ backup.size }}</td>
                                                <td>{{ backup.date }}</td>
                                                <td class="text-right">
                                                    <a :href="`/settings/backup/${backup.id}/download`" class="action-btn text-green" title="Download">
                                                        <v-icon icon="mdi-download" size="18" />
                                                    </a>
                                                    <button @click="deleteBackup(backup.id)" class="action-btn text-red" title="Delete">
                                                        <v-icon icon="mdi-delete-outline" size="18" />
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <div v-else class="empty-state">
                                        No database backups created yet.
                                    </div>
                                </div>
                            </div>

                            <div class="content-card" v-if="activeSettingTab === 'activity'" style="padding: 32px;">
                                <div class="card-header pb-4 border-b">
                                    <div>
                                        <h2 class="card-title">Activity Log</h2>
                                        <p class="card-sub">Review system activity and user actions.</p>
                                    </div>
                                    <div style="display:flex; align-items:center; gap:12px;">
                                        <label style="font-size:13px; color:#6b7280;">Rows:</label>
                                        <select v-model="currentLimit" @change="changeLimit" class="form-input" style="width:70px; height:32px; padding:0 8px;">
                                            <option :value="10">10</option>
                                            <option :value="20">20</option>
                                            <option :value="50">50</option>
                                            <option :value="100">100</option>
                                        </select>
                                    </div>
                                </div>
                                <div style="margin-top: 24px; overflow-x: auto; width: 100%;">
                                    <table v-if="activityLogs?.data?.length > 0" class="data-table">
                                        <thead>
                                            <tr>
                                                <th>User</th>
                                                <th>Action</th>
                                                <th>Description</th>
                                                <th>IP Address</th>
                                                <th>Time</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="log in activityLogs.data" :key="log.id">
                                                <td>
                                                    <div class="user-cell">
                                                        <img v-if="log.user?.profile_logo" :src="log.user.profile_logo" class="avatar-mini" />
                                                        <span v-else class="avatar-mini-placeholder"><v-icon icon="mdi-account" size="12"/></span>
                                                        <span>{{ log.user ? log.user.name : 'System/Guest' }}</span>
                                                    </div>
                                                </td>
                                                <td><span class="badge gray">{{ log.action }}</span></td>
                                                <td>{{ log.description }}</td>
                                                <td>{{ log.ip_address }}</td>
                                                <td>{{ new Date(log.created_at).toLocaleString() }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <div v-else class="empty-state">
                                        No activity logs found.
                                    </div>

                                    
                                    <div v-if="activityLogs?.links?.length > 3" class="pagination-wrap">
                                        <button v-for="link in activityLogs.links" 
                                                :key="link.label"
                                                @click="link.url ? router.get(link.url, { limit: currentLimit, tab: activeSettingTab }, { preserveScroll: true }) : null"
                                                v-html="link.label"
                                                class="pagination-btn"
                                                :class="{ active: link.active, disabled: !link.url }">
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="content-card" v-if="activeSettingTab === 'users'" style="padding: 32px;">
                                <div class="card-header pb-4 border-b">
                                    <div>
                                        <h2 class="card-title">User Login Status</h2>
                                        <p class="card-sub">Monitor active user accounts and toggle their login access.</p>
                                    </div>
                                    <div style="display:flex; align-items:center; gap:16px;">
                                        <div style="display:flex; align-items:center; gap:8px;">
                                            <label style="font-size:13px; color:#6b7280;">Rows:</label>
                                            <select v-model="currentLimit" @change="changeLimit" class="form-input" style="width:70px; height:32px; padding:0 8px;">
                                                <option :value="10">10</option>
                                                <option :value="20">20</option>
                                                <option :value="50">50</option>
                                            </select>
                                        </div>
                                        <button @click="router.get('/users')" class="btn-outline-green">
                                            Manage Users
                                        </button>
                                    </div>
                                </div>
                                <div style="margin-top: 24px; overflow-x: auto; width: 100%;">
                                    <table v-if="users?.data?.length > 0" class="data-table">
                                        <thead>
                                            <tr>
                                                <th>User</th>
                                                <th>Email</th>
                                                <th>Joined Date</th>
                                                <th class="text-right">Login Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="user in users.data" :key="user.id">
                                                <td style="font-weight: 500;">{{ user.name }}</td>
                                                <td>{{ user.email }}</td>
                                                <td>{{ new Date(user.created_at).toLocaleDateString() }}</td>
                                                <td class="text-right">
                                                    <div style="display: flex; justify-content: flex-end; align-items: center; gap: 8px;">
                                                        <span :class="['badge', user.is_active ? 'green' : 'red']">
                                                            {{ user.is_active ? 'Active' : 'Inactive' }}
                                                        </span>
                                                        <button 
                                                            v-if="user.id !== auth?.id"
                                                            @click="toggleUserStatus(user)" 
                                                            class="toggle-btn" 
                                                            :class="{ 'is-active': user.is_active }"
                                                            :title="user.is_active ? 'Deactivate Login' : 'Activate Login'">
                                                            <div class="toggle-circle"></div>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    
                                  
                                    <div v-if="users?.links?.length > 3" class="pagination-wrap">
                                        <button v-for="link in users.links" 
                                                :key="link.label"
                                                @click="link.url ? router.get(link.url, { limit: currentLimit, tab: activeSettingTab }, { preserveScroll: true }) : null"
                                                v-html="link.label"
                                                class="pagination-btn"
                                                :class="{ active: link.active, disabled: !link.url }">
                                        </button>
                                    </div>
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

const props = defineProps({
    users: Object,
    activityLogs: Object,
    backups: Array,
    fieldOptions: { type: Object, default: () => ({}) },
    plans: { type: Array, default: () => [] },
    limit: [String, Number],
    tab: { type: String, default: 'profile' }
});

const can = (permission) => {
    return auth.value?.is_admin || auth.value?.permissions?.includes(permission);
};

const adminOpen = ref(false);
const fileInput = ref(null);
const avatarPreview = ref(null);
const activeSettingTab = ref(props.tab || 'profile');
const currentLimit = ref(props.limit || 10);

const changeLimit = () => {
    router.get('/settings', { limit: currentLimit.value, tab: activeSettingTab.value }, { preserveState: true, preserveScroll: true });
};

const settingsForm = useForm({
    name: auth.value?.name || '',
    email: auth.value?.email || '',
    password: '',
    password_confirmation: '',
    profile_logo: null,
});

const dynamicFields = [
    { key: 'biz_type', label: 'Business Type' },
    { key: 'source', label: 'Lead Source' },
    { key: 'division', label: 'Division' },
    { key: 'township', label: 'Township' },
    { key: 'product', label: 'Product' },
    { key: 'channel', label: 'Channel' },
    { key: 'package', label: 'Package' },
];

const planForm = useForm({
    name: '',
    staff_limit: 1,
    duration_days: 30,
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

const deletePlan = (id) => {
    if(confirm('Are you sure you want to delete this plan?')) {
        router.delete(`/plans/${id}`);
    }
};

const fieldForms = ref({});
dynamicFields.forEach(f => {
    fieldForms.value[f.key] = (props.fieldOptions[f.key] || []).join(', ');
});

const saveFieldOptions = (key) => {
    const rawVal = fieldForms.value[key] || '';
    const optionsArray = rawVal.split(',').map(s => s.trim()).filter(s => s !== '');
    
    router.post('/settings/tenant-fields', {
        field_name: key,
        options: optionsArray
    }, {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Saved!',
                text: 'Dropdown options updated successfully.',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        }
    });
};

const goToDashboard = () => {
    router.get('/dashboard');
};

const logout = () => router.post('/logout');

const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        settingsForm.profile_logo = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            avatarPreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const saveSettings = () => {
    settingsForm.post('/settings', {
        preserveScroll: true,
        onSuccess: () => {
            settingsForm.password = '';
            settingsForm.password_confirmation = '';
            settingsForm.profile_logo = null; 
            Swal.fire({
                title: 'Updated!',
                text: 'Your settings have been saved.',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            });
        }
    });
};
const isGeneratingBackup = ref(false);

const generateBackup = () => {
    isGeneratingBackup.value = true;
    router.post('/settings/backup', {}, {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                title: 'Success',
                text: 'Database backup generated successfully!',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
        },
        onFinish: () => {
            isGeneratingBackup.value = false;
        }
    });
};

const deleteBackup = (id) => {
    Swal.fire({
        title: 'Delete Backup?',
        text: 'Are you sure you want to delete this backup file?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/settings/backup/${id}`, { preserveScroll: true });
        }
    });
};

const toggleUserStatus = (user) => {
    router.post(`/users/${user.id}/toggle-active`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            const status = !user.is_active ? 'activated' : 'deactivated';
            Swal.fire({
                title: 'Status Updated',
                text: `${user.name} has been ${status}.`,
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            });
        }
    });
};
</script>

<style scoped>
@import '../../css/settings.css';
</style>


