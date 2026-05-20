<template>
    <v-app theme="light">
        <div class="dash-root">

            <header class="topbar">
                <div class="topbar-left">
                    <img src="/images/MOJOERESTO_ASSET_LOGO_BLACK800-2 1.png"
                         alt="Pipeline" class="nav-logo" />
                </div>
                <div class="topbar-right">
                    <div class="admin-menu" @click="adminOpen = !adminOpen" style="padding: 6px 14px 6px 16px; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 30px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); transition: all 0.2s;">
                        <div style="display: flex; flex-direction: column; align-items: flex-end; line-height: 1.2; margin-right: 12px;">
                            <span style="font-size: 0.88rem; font-weight: 600; color: #111827;">
                                {{ auth?.name ?? 'Admin' }}
                                <span v-if="auth?.company_name" style="color: #6b7280; font-weight: 400; font-size: 0.8rem;"> @ {{ auth.company_name }}</span>
                            </span>
                            <span v-if="auth?.role" style="font-size: 0.7rem; color: #2ecc5e; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px;">{{ auth.role }}</span>
                        </div>
                        <img v-if="auth?.profile_logo" :src="auth.profile_logo" class="profile-avatar-small" alt="Avatar" style="margin-right: 0; width: 34px; height: 34px; box-shadow: none; border: 2px solid #e5e7eb;"/>
                        <div v-else style="width: 34px; height: 34px; border-radius: 50%; background: #f3f4f6; border: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: center; color: #4b5563; font-weight: 600; font-size: 1rem;">
                            {{ (auth?.name || 'A').charAt(0).toUpperCase() }}
                        </div>
                        <v-icon :icon="adminOpen ? 'mdi-chevron-up' : 'mdi-chevron-down'"
                            size="18" color="#9ca3af" style="margin-left: 8px;" />
                        <div v-if="adminOpen" class="admin-dropdown" @click.stop style="top: calc(100% + 8px); border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
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
                        <Menu activeTab="users" />
                    </div>

                  
                    <div class="breadcrumb-card">
                        <div class="bc-home-icon">
                            <v-icon icon="mdi-home" size="16" color="#fff" />
                        </div>
                        <span class="bc-sep">›</span>
                        <span class="bc-text">User Management</span>
                        <span class="bc-sep">›</span>
                        <span class="bc-active" style="text-transform: capitalize">{{ tab }}</span>
                    </div>

                    
                    <div class="content-card" style="padding: 24px;">
                        
                        <div class="card-header">
                            <div>
                                <h2 class="card-title" style="text-transform: capitalize">{{ tab }} Management</h2>
                                <p class="card-sub" v-if="tab === 'users'">Manage system users and assign roles to them.</p>
                                <p class="card-sub" v-if="tab === 'roles'">Create and manage roles, e.g., 'Editor', and assign permissions.</p>
                                <p class="card-sub" v-if="tab === 'permissions'">Manage granular access levels for your application.</p>
                            </div>
                            <div class="card-actions">
                                <button v-if="can('submenu_users')" class="btn-outline-green" :class="{ pillactive: tab === 'users' }" @click="tab = 'users'">Users</button>
                                <button v-if="can('submenu_roles')" class="btn-outline-green" :class="{ pillactive: tab === 'roles' }" @click="tab = 'roles'">Roles</button>
                                <button v-if="can('submenu_permissions')" class="btn-outline-green" :class="{ pillactive: tab === 'permissions' }" @click="tab = 'permissions'">Permissions</button>
                                <button v-if="auth?.role === 'Company Super Admin'" class="btn-outline-green" :class="{ pillactive: tab === 'organizations' }" @click="tab = 'organizations'">Organizations</button>
                                <!-- View toggle (only on Users tab) -->
                                <div v-if="tab === 'users'" class="view-toggle-wrap">
                                    <button :class="['view-toggle-btn', !hierView ? 'active' : '']" @click="hierView = false">
                                        <v-icon icon="mdi-table" size="14" /> Table
                                    </button>
                                    <button :class="['view-toggle-btn', hierView ? 'active' : '']" @click="hierView = true">
                                        <v-icon icon="mdi-file-tree" size="14" /> Hierarchy
                                    </button>
                                </div>
                                <button class="btn-solid-green" style="margin-left:8px;" @click="openAddModal"
                                    v-if="(tab === 'users' && (can('create_users') || auth?.is_admin)) || (tab === 'organizations' && auth?.role === 'Company Super Admin') || (tab !== 'users' && tab !== 'organizations' && (can('manage_roles') || auth?.is_admin))">
                                    <v-icon icon="mdi-plus" size="15" />
                                    Add {{ tab === 'organizations' ? 'Organization' : tab.slice(0, -1) }}
                                </button>
                            </div>
                        </div>

                        <div class="table-wrap" style="margin-top: 20px; overflow-x: auto; width: 100%;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                       
                                        <template v-if="tab === 'users'">
                                            <th>Name</th>
                                            <th>Organization</th>
                                            <th>Phone</th>
                                            <th>Email</th>
                                            <th>Role(s)</th>
                                            <th>Status</th>
                                            <th v-if="auth?.is_admin">Duration (Days)</th>
                                            <th>Action</th>
                                        </template>

                                      
                                        <template v-if="tab === 'roles'">
                                            <th>Name</th>
                                            <th>Permissions</th>
                                            <th>Action</th>
                                        </template>

                                      
                                        <template v-if="tab === 'permissions'">
                                            <th>Name</th>
                                        </template>
                                    </tr>
                                </thead>
                                <tbody>
                                 
                                    <template v-if="tab === 'organizations'">
                                        <tr v-for="org in props.organizations" :key="org.id">
                                            <td><strong>{{ org.name }}</strong></td>
                                            <td>{{ org.users?.length ?? 0 }} members</td>
                                            <td class="action-td">
                                                <div class="btn-wrap">
                                                    <button class="btn-edit" @click="openEditOrg(org)"><v-icon icon="mdi-pencil" size="12" /> Edit</button>
                                                    <button class="btn-delete" @click="deleteOrg(org.id)"><v-icon icon="mdi-delete" size="12" /> Delete</button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr v-if="!props.organizations?.length">
                                            <td colspan="3" class="text-center py-4 text-gray-500">No organizations yet. Click "Add Organization" to create one.</td>
                                        </tr>
                                    </template>

                                    <!-- ── USERS TAB ── -->
                                    <template v-if="tab === 'users'">
                                        <!-- HIERARCHY VIEW -->
                                        <template v-if="hierView">
                                            <tr><td colspan="8" style="padding:0; border:none;">
                                            <div class="hier-wrap">

                                                <!-- Super Admins -->
                                                <div class="hier-role-section" v-if="superAdmins.length">
                                                    <div class="hier-role-label hier-sa"><v-icon icon="mdi-shield-crown" size="14"/> Company Super Admin</div>
                                                    <div class="hier-card" v-for="u in superAdmins" :key="u.id">
                                                        <div class="hier-avatar hier-avatar-sa">{{ u.name.charAt(0) }}</div>
                                                        <div class="hier-info">
                                                            <div class="hier-name">{{ u.name }}</div>
                                                            <div class="hier-meta">{{ u.email }}</div>
                                                            <div class="hier-orgs">
                                                                <span v-for="org in u.organizations" :key="org.id" class="hier-org-badge hier-org-sa">{{ org.name }}</span>
                                                            </div>
                                                        </div>
                                                        <div class="hier-status" :class="u.is_active ? 'h-active' : 'h-inactive'">{{ u.is_active ? 'Active' : 'Inactive' }}</div>
                                                        <div class="hier-actions">
                                                            <button class="btn-edit" @click="openEditUser(u)" v-if="can('edit_users') || auth?.is_admin"><v-icon icon="mdi-pencil" size="12"/> Edit</button>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Managers + their Staff -->
                                                <div class="hier-role-section" v-if="managers.length">
                                                    <div class="hier-role-label hier-mgr"><v-icon icon="mdi-account-tie" size="14"/> Managers &amp; Their Staff</div>
                                                    <div v-for="mgr in managers" :key="mgr.id" class="hier-manager-block">
                                                        <!-- Manager row -->
                                                        <div class="hier-card hier-card-mgr" @click="toggleManager(mgr.id)">
                                                            <div class="hier-avatar hier-avatar-mgr">{{ mgr.name.charAt(0) }}</div>
                                                            <div class="hier-info">
                                                                <div class="hier-name">{{ mgr.name }} <span class="hier-role-pill">Manager</span></div>
                                                                <div class="hier-meta">{{ mgr.email }}</div>
                                                                <div class="hier-orgs">
                                                                    <span v-for="org in mgr.organizations" :key="org.id" class="hier-org-badge hier-org-mgr">{{ org.name }}</span>
                                                                </div>
                                                            </div>
                                                            <div class="hier-staff-count">
                                                                <v-icon icon="mdi-account-group" size="13"/> {{ staffUnder(mgr.id).length }} Staff
                                                            </div>
                                                            <div class="hier-status" :class="mgr.is_active ? 'h-active' : 'h-inactive'">{{ mgr.is_active ? 'Active' : 'Inactive' }}</div>
                                                            <div class="hier-actions">
                                                                <button class="btn-edit" @click.stop="openEditUser(mgr)" v-if="can('edit_users') || auth?.is_admin"><v-icon icon="mdi-pencil" size="12"/> Edit</button>
                                                                <button class="btn-delete" @click.stop="deleteUser(mgr.id)" v-if="(can('delete_users') || auth?.is_admin) && auth?.id !== mgr.id"><v-icon icon="mdi-delete" size="12"/> Delete</button>
                                                            </div>
                                                            <v-icon :icon="expandedManagers.includes(mgr.id) ? 'mdi-chevron-up' : 'mdi-chevron-down'" size="18" color="#9ca3af" style="margin-left:8px;"/>
                                                        </div>
                                                        <!-- Staff rows (collapsible) -->
                                                        <div class="hier-staff-list" v-show="expandedManagers.includes(mgr.id)">
                                                            <div v-if="!staffUnder(mgr.id).length" class="hier-empty">No staff assigned yet.</div>
                                                            <div class="hier-card hier-card-staff" v-for="s in staffUnder(mgr.id)" :key="s.id">
                                                                <div class="hier-tree-line"></div>
                                                                <div class="hier-avatar hier-avatar-staff">{{ s.name.charAt(0) }}</div>
                                                                <div class="hier-info">
                                                                    <div class="hier-name">{{ s.name }}</div>
                                                                    <div class="hier-meta">{{ s.email }} &bull; {{ s.phone || 'No phone' }}</div>
                                                                    <div class="hier-orgs">
                                                                        <span v-for="org in s.organizations" :key="org.id" class="hier-org-badge hier-org-staff">{{ org.name }}</span>
                                                                    </div>
                                                                </div>
                                                                <div class="hier-status" :class="s.is_active ? 'h-active' : 'h-inactive'">{{ s.is_active ? 'Active' : 'Inactive' }}</div>
                                                                <div class="hier-actions">
                                                                    <button class="btn-edit" @click="openEditUser(s)" v-if="can('edit_users') || auth?.is_admin"><v-icon icon="mdi-pencil" size="12"/> Edit</button>
                                                                    <button class="btn-delete" @click="deleteUser(s.id)" v-if="(can('delete_users') || auth?.is_admin) && auth?.id !== s.id"><v-icon icon="mdi-delete" size="12"/> Delete</button>
                                                                    <button class="btn-toggle" :class="s.is_active ? 'btn-deactivate' : 'btn-activate'" @click="toggleActive(s)" v-if="(can('edit_users') || auth?.is_admin) && auth?.id !== s.id">
                                                                        <v-icon :icon="s.is_active ? 'mdi-close-circle' : 'mdi-check-circle'" size="12"/>
                                                                        {{ s.is_active ? 'Deactivate' : 'Approve' }}
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Unassigned Users -->
                                                <div class="hier-role-section" v-if="unassignedUsers.length">
                                                    <div class="hier-role-label hier-user"><v-icon icon="mdi-account-question" size="14"/> Unassigned / No Manager</div>
                                                    <div class="hier-card" v-for="u in unassignedUsers" :key="u.id">
                                                        <div class="hier-avatar hier-avatar-staff">{{ u.name.charAt(0) }}</div>
                                                        <div class="hier-info">
                                                            <div class="hier-name">{{ u.name }}</div>
                                                            <div class="hier-meta">{{ u.email }}</div>
                                                            <div class="hier-orgs">
                                                                <span v-for="org in u.organizations" :key="org.id" class="hier-org-badge hier-org-staff">{{ org.name }}</span>
                                                                <span v-if="!u.organizations?.length" style="color:#9ca3af;font-size:0.78rem;">No org assigned</span>
                                                            </div>
                                                        </div>
                                                        <div class="hier-status" :class="u.is_active ? 'h-active' : 'h-inactive'">{{ u.is_active ? 'Active' : 'Inactive' }}</div>
                                                        <div class="hier-actions">
                                                            <button class="btn-edit" @click="openEditUser(u)" v-if="can('edit_users') || auth?.is_admin"><v-icon icon="mdi-pencil" size="12"/> Edit</button>
                                                            <button class="btn-delete" @click="deleteUser(u.id)" v-if="(can('delete_users') || auth?.is_admin) && auth?.id !== u.id"><v-icon icon="mdi-delete" size="12"/> Delete</button>
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>
                                        </td></tr>
                                    </template>

                                    <!-- ── TABLE VIEW ── -->
                                    <template v-else>
                                        <tr v-for="user in props.users" :key="user.id">
                                            <td>{{ user.name }}</td>
                                            <td>
                                                <span v-if="user.organizations?.length" style="display:flex;flex-wrap:wrap;gap:4px;">
                                                    <span v-for="org in user.organizations" :key="org.id" style="background:#dcfce7;color:#166534;padding:2px 8px;border-radius:12px;font-size:0.78rem;font-weight:600;">{{ org.name }}</span>
                                                </span>
                                                <span v-else style="color:#9ca3af">-</span>
                                            </td>
                                            <td>{{ user.phone || '-' }}</td>
                                            <td>{{ user.email }}</td>
                                            <td>
                                                <span v-for="ro in user.all_roles" :key="ro.id" class="role-badge">{{ ro.name }}</span>
                                                <span v-if="!user.all_roles || user.all_roles.length === 0" class="role-badge" style="background: #f3f4f6; color: #6b7280; border: 1px dashed #9ca3af;">Pending Approval</span>
                                            </td>
                                            <td>
                                                <span class="status-btn" :class="user.is_active ? 's-active' : 's-inactive'" style="cursor:default">
                                                    {{ user.is_active ? 'Active' : 'Deactivated' }}
                                                </span>
                                            </td>
                                            <td v-if="auth?.is_admin">
                                                <div v-if="user.plan_expired_at" :style="getExpireStyle(user.plan_expired_at)">
                                                    {{ getRemainingDays(user.plan_expired_at) }} Days
                                                    <v-icon v-if="isNearExpiration(user.plan_expired_at)" icon="mdi-alarm" size="14" style="margin-left: 4px;" />
                                                </div>
                                                <span v-else style="color: #9ca3af; font-size: 0.85rem;">-</span>
                                            </td>
                                            <td class="action-td">
                                                <div class="btn-wrap">
                                                    <button class="btn-detail" @click="openDetail(user)" :disabled="!user.staff?.length"><v-icon icon="mdi-information-outline" size="12" style="margin-right:4px;" /> Detail</button>
                                                    <button class="btn-edit" @click="openEditUser(user)" v-if="can('edit_users') || auth?.is_admin"><v-icon icon="mdi-pencil" size="12" /> Edit</button>
                                                    <button class="btn-delete" @click="deleteUser(user.id)" v-if="(can('delete_users') || auth?.is_admin) && auth?.id !== user.id"><v-icon icon="mdi-delete" size="12" /> Delete</button>
                                                    <button class="btn-toggle" :class="user.is_active ? 'btn-deactivate' : 'btn-activate'" @click="toggleActive(user)" v-if="(can('edit_users') || auth?.is_admin) && auth?.id !== user.id">
                                                        <v-icon :icon="user.is_active ? 'mdi-close-circle' : 'mdi-check-circle'" size="12" /> 
                                                        {{ user.is_active ? 'Deactivate' : 'Approve' }}
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr v-if="!props.users?.length">
                                            <td colspan="5" class="text-center py-4 text-gray-500">No users found.</td>
                                        </tr>
                                    </template>
                                    </template>
                                    <!-- /users tab -->

                                    <template v-if="tab === 'roles'">
                                        <tr v-for="ro in props.roles" :key="ro.id">
                                            <td><strong>{{ ro.name }}</strong></td>
                                            <td>
                                                <span v-for="p in ro.permissions" :key="p.id" class="role-badge" style="margin-bottom: 4px;">
                                                    {{ p.menu_name || p.name }}
                                                </span>
                                                <span v-if="!ro.permissions?.length" class="text-gray-400 text-sm">No permissions</span>
                                            </td>
                                            <td>
                                                <div style="display: flex; gap: 8px;">
                                                    <button class="btn-edit" @click="openEditRole(ro)">
                                                        <v-icon icon="mdi-shield-edit" size="14" /> Manage Permissions
                                                    </button>
                                                    <button class="btn-delete" @click="deleteRole(ro.id)" v-if="can('manage_roles')">
                                                        <v-icon icon="mdi-delete" size="12" /> Delete
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>

                                    <template v-if="tab === 'permissions'">
                                        <tr v-for="p in props.permissions" :key="p.id">
                                            <td>
                                                <strong>{{ p.menu_name || p.name }}</strong>
                                                <span class="text-gray-400 text-sm ml-2">({{ p.name }})</span>
                                            </td>
                                        </tr>
                                        <tr v-if="!props.permissions?.length">
                                            <td colspan="1" class="text-center py-4 text-gray-500">No permissions seeded.</td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
            </div>

            <div v-if="detailDialog" class="custom-modal-backdrop" @click.self="detailDialog = false">
                <div class="custom-modal-card" style="max-width: 500px;">
                    <div class="modal-header">
                        <h3>User Details</h3>
                        <button class="modal-close" @click="detailDialog = false">✖</button>
                    </div>
                    <div class="modal-body" v-if="activeDetailUser" style="display:flex; flex-direction:column; gap: 16px;">
                        <div style="background: #f9fafb; padding: 16px; border-radius: 8px; border: 1px solid #e5e7eb;">
                            <div style="font-size: 1.1rem; font-weight: 600; color: #111827;">{{ activeDetailUser.name }}</div>
                            <div v-if="activeDetailUser.company_name" style="font-size: 0.95rem; font-weight: 500; color: #374151; margin-top: 4px;">🏢 {{ activeDetailUser.company_name }}</div>
                            <div style="font-size: 0.9rem; color: #6b7280; margin-top: 4px;">✉️ {{ activeDetailUser.email }}</div>
                            <div v-if="activeDetailUser.phone" style="font-size: 0.9rem; color: #6b7280; margin-top: 2px;">📞 {{ activeDetailUser.phone }}</div>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f3f4f6; padding-bottom: 8px;">
                                <span style="font-size:0.85rem; color:#6b7280; font-weight:600;">System Role</span>
                                <span style="font-size:0.85rem; font-weight:500;">
                                    {{ activeDetailUser.all_roles?.map(r => r.name).join(', ') || 'None' }}
                                </span>
                            </div>

                            <div v-if="activeDetailUser.tenant && activeDetailUser.tenant.id !== activeDetailUser.id" style="display: flex; justify-content: space-between; border-bottom: 1px solid #f3f4f6; padding-bottom: 8px;">
                                <span style="font-size:0.85rem; color:#6b7280; font-weight:600;">Managed By (Owner)</span>
                                <span style="font-size:0.85rem; font-weight:500; color: #0284c7;">
                                    {{ activeDetailUser.tenant.name }} ({{ activeDetailUser.tenant.email }})
                                </span>
                            </div>

                            <div v-if="activeDetailUser.plan" style="display: flex; justify-content: space-between; border-bottom: 1px solid #f3f4f6; padding-bottom: 8px;">
                                <span style="font-size:0.85rem; color:#6b7280; font-weight:600;">SaaS Plan</span>
                                <span style="font-size:0.85rem; font-weight:600; color: #2ecc5e;">
                                    {{ activeDetailUser.plan.name }}
                                </span>
                            </div>
                            
                            <div v-if="activeDetailUser.plan" style="display: flex; justify-content: space-between; border-bottom: 1px solid #f3f4f6; padding-bottom: 8px;">
                                <span style="font-size:0.85rem; color:#6b7280; font-weight:600;">Staff Limit</span>
                                <span style="font-size:0.85rem; font-weight:500;">
                                    {{ activeDetailUser.plan.staff_limit }} users max
                                </span>
                            </div>

                            <div v-if="auth?.is_admin && activeDetailUser.staff?.length" style="display: flex; flex-direction: column; border-bottom: 1px solid #f3f4f6; padding-bottom: 8px;">
                                <span style="font-size:0.85rem; color:#6b7280; font-weight:600; margin-bottom: 6px;">Staff Managed</span>
                                <div style="display:flex; flex-direction:column; gap:4px;">
                                    <span v-for="st in activeDetailUser.staff" :key="st.id" style="font-size:0.8rem; background: #e5e7eb; padding: 2px 8px; border-radius: 4px; display:inline-block; width: fit-content;">
                                        {{ st.name }} ({{ st.email }})
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

          
            <div v-if="userDialog" class="custom-modal-backdrop" @click.self="userDialog = false">
                <div class="custom-modal-card">
                    <div class="modal-header">
                        <h3>{{ isEdit ? 'Edit User' : 'Add New User' }}</h3>
                        <button class="modal-close" @click="userDialog = false">✖</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input v-model="userForm.name" type="text" class="form-input" placeholder="Enter full name" />
                            <span v-if="userForm.errors.name" class="error-text">{{ userForm.errors.name }}</span>
                        </div>
                        <div class="form-group" v-if="auth?.is_admin">
                            <label>Company Name</label>
                            <input v-model="userForm.company_name" type="text" class="form-input" placeholder="Enter company name" />
                        </div>
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input v-model="userForm.phone" type="text" class="form-input" placeholder="Enter phone number" />
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input v-model="userForm.email" type="email" class="form-input" placeholder="Enter email address" />
                            <span v-if="userForm.errors.email" class="error-text">{{ userForm.errors.email }}</span>
                        </div>
                        <div class="form-group">
                            <label>{{ isEdit ? 'New Password (leave blank to keep)' : 'Password' }}</label>
                            <input v-model="userForm.password" type="password" class="form-input" placeholder="Enter password" />
                            <span v-if="userForm.errors.password" class="error-text">{{ userForm.errors.password }}</span>
                        </div>
                        <div class="form-group">
                            <label>Confirm Password</label>
                            <input v-model="userForm.password_confirmation" type="password" class="form-input" placeholder="Confirm password" />
                        </div>
                        <div class="form-group" v-if="props.organizations?.length && !auth?.is_admin">
                            <label>Assign to Organization</label>
                            <div class="org-checkbox-list">
                                <label
                                    v-for="org in props.organizations"
                                    :key="org.id"
                                    class="org-checkbox-item"
                                    :class="{ 'org-checked': userForm.organizations.includes(org.id) }"
                                >
                                    <input
                                        type="checkbox"
                                        :value="org.id"
                                        v-model="userForm.organizations"
                                        style="display:none;"
                                    />
                                    <span class="org-check-icon">
                                        <v-icon
                                            :icon="userForm.organizations.includes(org.id) ? 'mdi-checkbox-marked-circle' : 'mdi-checkbox-blank-circle-outline'"
                                            size="16"
                                        />
                                    </span>
                                    <span>{{ org.name }}</span>
                                </label>
                            </div>
                            <span class="help-text">Click to select / deselect organizations.</span>
                        </div>
                        <div class="form-group">
                            <label>Assign Role</label>
                            <select v-model="userForm.roles" multiple class="form-input select-multiple">
                                <option v-for="r in props.roles" :key="r.id" :value="r.name">{{ r.name }}</option>
                            </select>
                            <span class="help-text">Hold Ctrl/Cmd to select multiple.</span>
                        </div>
                        <template v-if="auth?.is_admin">
                            <div class="form-group" style="margin-top:16px;">
                                <label>Assign SaaS Plan</label>
                                <select v-model="userForm.plan_id" @change="updateExpireDate" class="form-input">
                                    <option :value="null">None</option>
                                    <option v-for="pl in props.plans" :key="pl.id" :value="pl.id">{{ pl.name }} (Lmt: {{ pl.staff_limit }})</option>
                                </select>
                            </div>
                        </template>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-cancel" @click="userDialog = false">Cancel</button>
                        <button class="btn-save" @click="saveUser" :disabled="userForm.processing">
                            {{ userForm.processing ? 'Saving...' : 'Save User' }}
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="roleDialog" class="custom-modal-backdrop" @click.self="roleDialog = false">
                <div class="custom-modal-card">
                    <div class="modal-header">
                        <h3>Manage Role: {{ activeRole?.name }}</h3>
                        <button class="modal-close" @click="roleDialog = false">✖</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group" style="margin-bottom: 16px;">
                            <label>Role Name</label>
                            <input v-model="roleForm.name" type="text" class="form-input" placeholder="e.g. editor" />
                        </div>
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label>Menu Filter</label>
                            <select v-model="activeGroup" class="form-input">
                                <option v-for="(perms, groupName) in groupedPermissions" :key="groupName" :value="groupName">
                                    {{ groupName }}
                                </option>
                            </select>
                        </div>
                        <div class="form-group" v-if="activeGroup && groupedPermissions[activeGroup]">
                            <label>Assign [ {{ activeGroup }} ] Permissions to this Role</label>
                            <div class="permissions-list mt-2" style="display: flex; flex-direction: column; gap: 8px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px; max-height: 250px; overflow-y: auto;">
                                <label v-for="perm in groupedPermissions[activeGroup]" :key="perm.id" class="checkbox-label" style="align-items: center; justify-content: flex-start; gap: 8px; font-size: .9rem;">
                                    <input type="checkbox" :value="perm.name" v-model="roleForm.permissions" style="margin-top:0;" />
                                    <span>
                                        {{ perm.menu_name || perm.name }} 
                                        <small style="color: #94a3b8; margin-left:4px;">({{ perm.name }})</small>
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-cancel" @click="roleDialog = false">Cancel</button>
                        <button class="btn-save" @click="saveRolePermissions" :disabled="roleForm.processing">
                            {{ roleForm.processing ? 'Saving...' : 'Save Permissions' }}
                        </button>
                    </div>
                </div>
            </div>
          
            <div v-if="createRoleDialog" class="custom-modal-backdrop" @click.self="createRoleDialog = false">
                <div class="custom-modal-card">
                    <div class="modal-header">
                        <h3>Add New Role</h3>
                        <button class="modal-close" @click="createRoleDialog = false">✖</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Role Name</label>
                            <input v-model="newRoleForm.name" type="text" class="form-input" placeholder="e.g. editor, manager" />
                            <span v-if="newRoleForm.errors.name" class="error-text">{{ newRoleForm.errors.name }}</span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-cancel" @click="createRoleDialog = false">Cancel</button>
                        <button class="btn-save" @click="saveNewRole" :disabled="newRoleForm.processing">
                            {{ newRoleForm.processing ? 'Saving...' : 'Save Role' }}
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="createPermDialog" class="custom-modal-backdrop" @click.self="createPermDialog = false">
                <div class="custom-modal-card">
                    <div class="modal-header">
                        <h3>Add New Permission</h3>
                        <button class="modal-close" @click="createPermDialog = false">✖</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Display Name</label>
                            <input v-model="newPermForm.menu_name" type="text" class="form-input" placeholder="e.g. Setting Menu" />
                        </div>
                        <div class="form-group">
                            <label>System Permission Name</label>
                            <input v-model="newPermForm.name" type="text" class="form-input" placeholder="e.g. menu_setting" />
                            <span v-if="newPermForm.errors.name" class="error-text">{{ newPermForm.errors.name }}</span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-cancel" @click="createPermDialog = false">Cancel</button>
                        <button class="btn-save" @click="saveNewPerm" :disabled="newPermForm.processing">
                            {{ newPermForm.processing ? 'Saving...' : 'Save Permission' }}
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Organization Create/Edit Modal -->
            <div v-if="orgDialog" class="custom-modal-backdrop" @click.self="orgDialog = false">
                <div class="custom-modal-card" style="max-width: 480px;">
                    <div class="modal-header">
                        <h3>{{ isEditOrg ? 'Edit Organization' : 'Add New Organization' }}</h3>
                        <button class="modal-close" @click="orgDialog = false">✖</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Organization Name</label>
                            <input v-model="orgForm.name" type="text" class="form-input" placeholder="e.g. Organization A, Yangon Branch" />
                            <span v-if="orgForm.errors.name" class="error-text">{{ orgForm.errors.name }}</span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-cancel" @click="orgDialog = false">Cancel</button>
                        <button class="btn-save" @click="saveOrg" :disabled="orgForm.processing">
                            {{ orgForm.processing ? 'Saving...' : isEditOrg ? 'Update' : 'Create Organization' }}
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </v-app>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { router, usePage, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import Menu from '@/Components/Menu.vue';

const props = defineProps({
    users: { type: Array, default: () => [] },
    roles: { type: Array, default: () => [] },
    permissions: { type: Array, default: () => [] },
    plans: { type: Array, default: () => [] },
    organizations: { type: Array, default: () => [] },
});

const page = usePage();
const auth = computed(() => page.props.auth?.user);
const adminOpen = ref(false);
const tab = ref('users');

const getRemainingDays = (dateString) => {
    if (!dateString) return 0;
    const diff = new Date(dateString) - new Date();
    const days = Math.ceil(diff / (1000 * 60 * 60 * 24));
    return days > 0 ? days : 0;
};

const isNearExpiration = (dateString) => {
    if (!dateString) return false;
    return getRemainingDays(dateString) <= 7;
};

const getExpireStyle = (dateString) => {
    if (isNearExpiration(dateString)) {
        return 'color: #ef4444; font-weight: 600; display: flex; align-items: center; font-size: 0.85rem;';
    }
    return 'color: #374151; font-size: 0.85rem;';
};

const groupedPermissions = computed(() => {
    const groups = {
        'Dashboard': [],
        'Lists': [],
        'Create': [],
        'User Management': [],
        'Setting': [],
        'Plan Menu': [],
        'Create Setting Menu': []
    };
    
    props.permissions.forEach(p => {
        if (p.name.includes('dashboard')) {
            groups['Dashboard'].push(p);
        } else if (p.name === 'menu_lists' || p.name === 'action_upload_lead' || p.name === 'action_download_csv') {
            groups['Lists'].push(p);
        } else if (p.name === 'menu_create' || p.name.startsWith('section_')) {
            groups['Create'].push(p);
        } else if (p.name === 'menu_user_management' || p.name.includes('user') || p.name.includes('role') || p.name.includes('permission')) {
            groups['User Management'].push(p);
        } else if (p.name.includes('plan')) {
            groups['Plan Menu'].push(p);
        } else if (p.name.includes('tenant_fields') || p.name.includes('setting')) {
            groups['Setting'].push(p);
        } else {
            // groups['Other Setup'].push(p);
        }
    });

    return Object.fromEntries(Object.entries(groups).filter(([k, v]) => v.length > 0));
});

const activeGroup = ref('Dashboard');
const toggleGroup = (groupName) => {
    if (activeGroup.value === groupName) {
        activeGroup.value = null;
    } else {
        activeGroup.value = groupName;
    }
};

const can = (permission) => {
    return auth.value?.permissions?.includes(permission);
};

const goToDashboard = () => {
    router.get('/dashboard');
};

const logout = () => router.post('/logout');
const detailDialog = ref(false);
const activeDetailUser = ref(null);

const openDetail = (u) => {
    activeDetailUser.value = u;
    detailDialog.value = true;
};

// ── Hierarchy view ──────────────────────────────────────────────
const hierView          = ref(false);
const expandedManagers  = ref([]);

const hasRole = (user, roleName) => user.all_roles?.some(r => r.name === roleName);

const superAdmins = computed(() =>
    (props.users || []).filter(u => hasRole(u, 'Company Super Admin'))
);
const managers = computed(() =>
    (props.users || []).filter(u => hasRole(u, 'Manager'))
);
const staffUnder = (managerId) =>
    (props.users || []).filter(u => u.manager_id === managerId && hasRole(u, 'User'));
const unassignedUsers = computed(() =>
    (props.users || []).filter(u =>
        hasRole(u, 'User') &&
        !managers.value.some(m => m.id === u.manager_id)
    )
);
const toggleManager = (id) => {
    const idx = expandedManagers.value.indexOf(id);
    if (idx >= 0) expandedManagers.value.splice(idx, 1);
    else expandedManagers.value.push(id);
};
// Expand all managers by default when switching to hierarchy view
watch(hierView, (v) => {
    if (v) expandedManagers.value = managers.value.map(m => m.id);
});

const userDialog = ref(false);
const isEdit = ref(false);

const userForm = useForm({
    id: null,
    name: '',
    company_name: '',
    phone: '',
    email: '',
    password: '',
    password_confirmation: '',
    roles: [],
    organizations: [],
    plan_id: null,
    plan_expired_at: '',
});

// Organization CRUD
const orgDialog = ref(false);
const isEditOrg = ref(false);
const orgForm = useForm({ id: null, name: '' });

const openEditOrg = (org) => {
    isEditOrg.value = true;
    orgForm.id = org.id;
    orgForm.name = org.name;
    orgDialog.value = true;
};

const saveOrg = () => {
    if (isEditOrg.value) {
        orgForm.put(`/organizations/${orgForm.id}`, { onSuccess: () => { orgDialog.value = false; orgForm.reset(); } });
    } else {
        orgForm.post('/organizations', { onSuccess: () => { orgDialog.value = false; orgForm.reset(); } });
    }
};

const deleteOrg = (id) => {
    Swal.fire({ title: 'Delete Organization?', text: 'All members will be detached.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444', confirmButtonText: 'Yes, delete' })
        .then(r => { if (r.isConfirmed) router.delete(`/organizations/${id}`); });
};

const updateExpireDate = () => {
    if (userForm.plan_id) {
        const plan = props.plans.find(p => p.id === userForm.plan_id);
        if (plan && plan.duration_in_days) {
            const date = new Date();
            date.setDate(date.getDate() + parseInt(plan.duration_in_days));
            userForm.plan_expired_at = date.toISOString().split('T')[0];
        }
    } else {
        userForm.plan_expired_at = '';
    }
};

const roleDialog = ref(false);
const activeRole = ref(null);
const roleForm = useForm({
    name: '',
    permissions: []
});

const createRoleDialog = ref(false);
const newRoleForm = useForm({ name: '' });

const createPermDialog = ref(false);
const newPermForm = useForm({ name: '', menu_name: '' });

const openAddModal = () => {
    if (tab.value === 'users') {
        isEdit.value = false;
        userForm.reset();
        userForm.clearErrors();
        userDialog.value = true;
    } else if (tab.value === 'organizations') {
        isEditOrg.value = false;
        orgForm.reset();
        orgDialog.value = true;
    } else if (tab.value === 'roles') {
        newRoleForm.reset();
        newRoleForm.clearErrors();
        createRoleDialog.value = true;
    } else if (tab.value === 'permissions') {
        newPermForm.reset();
        newPermForm.clearErrors();
        createPermDialog.value = true;
    }
};

const openEditUser = (u) => {
    isEdit.value = true;
    userForm.reset();
    userForm.clearErrors();
    userForm.id = u.id;
    userForm.name = u.name;
    userForm.company_name = u.company_name || '';
    userForm.phone = u.phone || '';
    userForm.email = u.email;
    userForm.roles = u.all_roles ? u.all_roles.map(r => r.name) : [];
    userForm.organizations = u.organizations ? u.organizations.map(o => o.id) : [];
    userForm.plan_id = u.plan_id || null;
    userForm.plan_expired_at = u.plan_expired_at ? u.plan_expired_at.split('T')[0] : '';
    userDialog.value = true;
};

const saveUser = () => {
    if (isEdit.value) {
        userForm.put(`/users/${userForm.id}`, {
            onSuccess: () => {
                userDialog.value = false;
                userForm.reset();
                userForm.clearErrors();
            }
        });
    } else {
        userForm.post('/users', {
            onSuccess: () => {
                userDialog.value = false;
                userForm.reset();
                userForm.clearErrors();
            }
        });
    }
};

const saveNewRole = () => {
    newRoleForm.post('/roles', {
        onSuccess: () => {
            createRoleDialog.value = false;
            newRoleForm.reset();
            Swal.fire('Success', 'Role added successfully', 'success');
        }
    });
};

const saveNewPerm = () => {
    newPermForm.post('/permissions', {
        onSuccess: () => {
            createPermDialog.value = false;
            newPermForm.reset();
            Swal.fire('Success', 'Permission added successfully', 'success');
        }
    });
};

const openEditRole = (role) => {
    activeRole.value = role;
    roleForm.name = role.name;
    roleForm.permissions = role.permissions.map(p => p.name);
    activeGroup.value = 'Dashboard'; 
    roleDialog.value = true;
};

const saveRolePermissions = () => {
    roleForm.put(`/roles/${activeRole.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            if (page.props.flash.error) {
                Swal.fire('Access Denied', page.props.flash.error, 'error');
                return;
            }
            roleForm.post(`/roles/${activeRole.value.id}/permissions`, {
                preserveScroll: true,
                onSuccess: () => {
                    if (page.props.flash.error) {
                        Swal.fire('Access Denied', page.props.flash.error, 'error');
                        return;
                    }
                    roleDialog.value = false;
                    roleForm.reset();
                    Swal.fire({
                        title: 'Success!',
                        text: 'Permissions and role configuration were saved successfully.',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            });
        }
    });
};

const deleteUser = (id) => {
    Swal.fire({
        title: 'Are you sure?',
        text: "This user will be soft-deleted. You can restore it later if needed.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#2ecc5e',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/users/${id}`, {
                onSuccess: () => {
                    Swal.fire({
                        title: 'Deleted!',
                        text: 'User has been successfully hidden.',
                        icon: 'success',
                        confirmButtonColor: '#2ecc5e'
                    });
                }
            });
        }
    });
};

const deleteRole = (id) => {
    Swal.fire({
        title: 'Are you sure?',
        text: "This role will be permanently deleted. Users with this role will lose their permissions.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#2ecc5e',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/roles/${id}`, {
                onSuccess: () => {
                    if (page.props.flash.error) {
                        Swal.fire('Access Denied', page.props.flash.error, 'error');
                        return;
                    }
                    Swal.fire({
                        title: 'Deleted!',
                        text: 'Role has been successfully deleted.',
                        icon: 'success',
                        confirmButtonColor: '#2ecc5e'
                    });
                }
            });
        }
    });
};

const toggleActive = (user) => {
    const actionText = user.is_active ? 'deactivate' : 'approve';
    
    Swal.fire({
        title: 'Change Status?',
        text: `Are you sure you want to ${actionText} this user?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#2ecc5e',
        cancelButtonColor: '#9ca3af',
        confirmButtonText: `Yes, ${actionText}!`
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(`/users/${user.id}/toggle-active`, {}, {
                preserveScroll: true,
                onSuccess: () => {
                    if (Object.keys(page.props.errors).length > 0) {
                        return; 
                    }
                    Swal.fire({
                        title: 'Updated!',
                        text: 'User status successfully updated.',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    });
                },
                onError: (errors) => {
                    if (errors.statusError) {
                        Swal.fire({
                            title: 'Cannot Approve',
                            text: errors.statusError,
                            icon: 'error',
                            confirmButtonColor: '#2ecc5e',
                            confirmButtonText: 'Understood'
                        });
                    }
                }
            });
        }
    });
};

</script>

<style scoped>
@import '../../css/usermanagement.css';

/* ── View Toggle ─────────────────────────────────────────── */
.view-toggle-wrap {
    display: flex; align-items: center;
    background: #f3f4f6; border: 1px solid #e5e7eb;
    border-radius: 8px; padding: 3px; gap: 2px;
    margin-left: 8px;
}
.view-toggle-btn {
    display: flex; align-items: center; gap: 4px;
    padding: 5px 12px; border-radius: 6px; border: none;
    background: transparent; color: #6b7280;
    font-size: 0.8rem; font-weight: 500; cursor: pointer;
    transition: all 0.15s;
}
.view-toggle-btn.active {
    background: #fff; color: #111827;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    font-weight: 600;
}

/* ── Hierarchy Wrap ──────────────────────────────────────── */
.hier-wrap { padding: 20px 16px; display: flex; flex-direction: column; gap: 24px; }

.hier-role-section { display: flex; flex-direction: column; gap: 8px; }
.hier-role-label {
    font-size: 0.75rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.6px; padding: 6px 12px; border-radius: 20px;
    display: inline-flex; align-items: center; gap: 5px;
    margin-bottom: 4px; width: fit-content;
}
.hier-sa   { background: #fef3c7; color: #92400e; }
.hier-mgr  { background: #dbeafe; color: #1e40af; }
.hier-user { background: #f3f4f6; color: #4b5563; }

/* Manager block */
.hier-manager-block { display: flex; flex-direction: column; gap: 0; }

/* Cards */
.hier-card {
    display: flex; align-items: center; gap: 12px;
    background: #fff; border: 1px solid #e5e7eb;
    border-radius: 10px; padding: 12px 14px;
    transition: box-shadow 0.15s;
}
.hier-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.07); }
.hier-card-mgr {
    cursor: pointer;
    background: #f8faff; border-color: #bfdbfe;
    border-radius: 10px 10px 0 0;
    border-bottom: none;
}
.hier-card-staff {
    background: #fafafa;
    border-radius: 0;
    border-top: 1px dashed #e5e7eb;
    padding-left: 40px;
    position: relative;
}
.hier-card-staff:last-child { border-radius: 0 0 10px 10px; }

/* Tree line */
.hier-tree-line {
    position: absolute; left: 22px; top: 0; bottom: 0;
    width: 2px; background: #e5e7eb;
}

/* Avatars */
.hier-avatar {
    width: 36px; height: 36px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 0.95rem; flex-shrink: 0;
}
.hier-avatar-sa    { background: #fef3c7; color: #92400e; }
.hier-avatar-mgr   { background: #dbeafe; color: #1e40af; }
.hier-avatar-staff { background: #dcfce7; color: #166534; }

/* Info */
.hier-info { flex: 1; min-width: 0; }
.hier-name { font-size: 0.88rem; font-weight: 600; color: #111827; }
.hier-meta { font-size: 0.78rem; color: #6b7280; margin-top: 1px; }
.hier-orgs { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 5px; }
.hier-org-badge {
    font-size: 0.72rem; font-weight: 600;
    padding: 2px 8px; border-radius: 10px;
}
.hier-org-sa    { background: #fef3c7; color: #92400e; }
.hier-org-mgr   { background: #dbeafe; color: #1e40af; }
.hier-org-staff { background: #dcfce7; color: #166534; }

.hier-role-pill {
    font-size: 0.68rem; font-weight: 700;
    background: #dbeafe; color: #1e40af;
    border-radius: 8px; padding: 1px 7px;
    margin-left: 6px; vertical-align: middle;
}
.hier-staff-count {
    font-size: 0.78rem; color: #6b7280;
    display: flex; align-items: center; gap: 4px;
    white-space: nowrap; margin-right: 8px;
}
.hier-status {
    font-size: 0.72rem; font-weight: 600;
    padding: 3px 10px; border-radius: 20px;
    white-space: nowrap;
}
.h-active   { background: #dcfce7; color: #166534; }
.h-inactive { background: #fee2e2; color: #991b1b; }
.hier-actions { display: flex; gap: 6px; flex-shrink: 0; }
.hier-staff-list { display: flex; flex-direction: column; }
.hier-empty {
    padding: 12px 40px; font-size: 0.82rem;
    color: #9ca3af; font-style: italic;
    background: #fafafa; border: 1px dashed #e5e7eb;
    border-top: none; border-radius: 0 0 10px 10px;
}

/* ── Org Checkbox List ───────────────────────────────────── */
.org-checkbox-list {
    display: flex; flex-direction: column; gap: 6px;
    max-height: 180px; overflow-y: auto;
    border: 1px solid #e5e7eb; border-radius: 8px;
    padding: 8px;
}
.org-checkbox-item {
    display: flex; align-items: center; gap: 8px;
    padding: 7px 10px; border-radius: 6px;
    cursor: pointer; font-size: 0.88rem; color: #374151;
    border: 1px solid transparent;
    transition: background 0.15s, border-color 0.15s;
    user-select: none;
}
.org-checkbox-item:hover {
    background: #f0fdf4; border-color: #bbf7d0;
}
.org-checkbox-item.org-checked {
    background: #dcfce7; border-color: #86efac; color: #166534; font-weight: 600;
}
.org-check-icon { color: #9ca3af; display: flex; }
.org-checkbox-item.org-checked .org-check-icon { color: #16a34a; }
</style>
