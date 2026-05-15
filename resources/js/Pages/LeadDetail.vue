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
                        <Menu activeTab="lists" />
                    </div>

                    <div class="breadcrumb-card">
                        <div class="bc-home-icon">
                            <v-icon icon="mdi-home" size="16" color="#fff" />
                        </div>
                        <span class="bc-sep">›</span>
                        <span class="bc-text" style="cursor: pointer" @click="goBack">Lead Management</span>
                        <span class="bc-sep">›</span>
                        <span class="bc-text" style="cursor: pointer" @click="goBack">Lists</span>
                        <span class="bc-sep">›</span>
                        <span class="bc-active">Lead Detail</span>
                    </div>

                    <div class="dash-master-wrapper">
                        <div class="content-card">
                            <div class="card-header pb-2" style="padding: 24px 24px 20px 24px; border-bottom: 1px solid #e5e7eb;">
                                <div>
                                    <h2 class="card-title" style="color:#2ecc5e;">{{ lead.business_name }}</h2>
                                    <p class="card-sub">Complete lead information and details</p>
                                </div>
                                <div class="card-actions">
                                    <button class="btn-outline-green" @click="goBack">
                                        <v-icon icon="mdi-arrow-left" size="15" /> Back to List
                                    </button>
                                    <button class="btn-solid-green" @click="editLead">
                                        <v-icon icon="mdi-pencil" size="15" /> Edit Lead
                                    </button>
                                </div>
                            </div>

                            <div style="padding: 24px;">
                                <!-- Contact Information Section -->
                                <div class="detail-section">
                                    <h3 class="detail-section-title">Contact Information</h3>
                                    <div class="detail-grid">
                                        <div class="detail-item">
                                            <label class="detail-label">Business Name</label>
                                            <div class="detail-value">{{ lead.business_name || '-' }}</div>
                                        </div>
                                        <div class="detail-item">
                                            <label class="detail-label">Contact Name</label>
                                            <div class="detail-value">{{ lead.contact_name || '-' }}</div>
                                        </div>
                                        <div class="detail-item">
                                            <label class="detail-label">First Name</label>
                                            <div class="detail-value">{{ lead.first_name || '-' }}</div>
                                        </div>
                                        <div class="detail-item">
                                            <label class="detail-label">Last Name</label>
                                            <div class="detail-value">{{ lead.last_name || '-' }}</div>
                                        </div>
                                        <div class="detail-item">
                                            <label class="detail-label">Email</label>
                                            <div class="detail-value">{{ lead.contact_email || '-' }}</div>
                                        </div>
                                        <div class="detail-item">
                                            <label class="detail-label">Phone</label>
                                            <div class="detail-value">{{ lead.phone || '-' }}</div>
                                        </div>
                                        <div class="detail-item">
                                            <label class="detail-label">Secondary Phone</label>
                                            <div class="detail-value">{{ lead.secondary_contact_number || '-' }}</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Location & Business Section -->
                                <div class="detail-section">
                                    <h3 class="detail-section-title">Location & Business Information</h3>
                                    <div class="detail-grid">
                                        <div class="detail-item">
                                            <label class="detail-label">Business Type</label>
                                            <div class="detail-value">{{ lead.biz_type || '-' }}</div>
                                        </div>
                                        <div class="detail-item">
                                            <label class="detail-label">Lead Source</label>
                                            <div class="detail-value">{{ lead.source || '-' }}</div>
                                        </div>
                                        <div class="detail-item">
                                            <label class="detail-label">Division</label>
                                            <div class="detail-value">{{ lead.division || '-' }}</div>
                                        </div>
                                        <div class="detail-item">
                                            <label class="detail-label">Township</label>
                                            <div class="detail-value">{{ lead.township || '-' }}</div>
                                        </div>
                                        <div class="detail-item full-width">
                                            <label class="detail-label">Address</label>
                                            <div class="detail-value">{{ lead.address || '-' }}</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Product & Pricing Section -->
                                <div class="detail-section">
                                    <h3 class="detail-section-title">Product & Pricing</h3>
                                    <div class="detail-grid">
                                        <div class="detail-item">
                                            <label class="detail-label">Product</label>
                                            <div class="detail-value">{{ lead.product || '-' }}</div>
                                        </div>
                                        <div class="detail-item">
                                            <label class="detail-label">Package</label>
                                            <div class="detail-value">{{ lead.package || '-' }}</div>
                                        </div>
                                        <div class="detail-item">
                                            <label class="detail-label">Plan</label>
                                            <div class="detail-value">{{ lead.plan || '-' }}</div>
                                        </div>
                                        <div class="detail-item">
                                            <label class="detail-label">Package Total</label>
                                            <div class="detail-value">{{ lead.package_total ? Number(lead.package_total).toLocaleString() : '-' }}</div>
                                        </div>
                                        <div class="detail-item">
                                            <label class="detail-label">Discount</label>
                                            <div class="detail-value">{{ lead.discount ? Number(lead.discount).toLocaleString() : '-' }}</div>
                                        </div>
                                        <div class="detail-item">
                                            <label class="detail-label">Amount</label>
                                            <div class="detail-value">{{ lead.amount ? Number(lead.amount).toLocaleString() : '-' }}</div>
                                        </div>
                                        <div class="detail-item full-width">
                                            <label class="detail-label">Note</label>
                                            <div class="detail-value">{{ lead.note || '-' }}</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Status & Channel Section -->
                                <div class="detail-section">
                                    <h3 class="detail-section-title">Status & Channel</h3>
                                    <div class="detail-grid">
                                        <div class="detail-item">
                                            <label class="detail-label">Status</label>
                                            <div class="detail-value">{{ lead.status || '-' }}</div>
                                        </div>
                                        <div class="detail-item">
                                            <label class="detail-label">Channel</label>
                                            <div class="detail-value">{{ lead.channel || '-' }}</div>
                                        </div>
                                        <div class="detail-item">
                                            <label class="detail-label">Referral</label>
                                            <div class="detail-value">{{ lead.is_referral ? 'Yes' : 'No' }}</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Important Dates Section -->
                                <div class="detail-section">
                                    <h3 class="detail-section-title">Important Dates</h3>
                                    <div class="detail-grid">
                                        <div class="detail-item">
                                            <label class="detail-label">Installation Appointment</label>
                                            <div class="detail-value">{{ lead.installation_appointment || '-' }}</div>
                                        </div>
                                        <div class="detail-item">
                                            <label class="detail-label">Est. Contract Date</label>
                                            <div class="detail-value">{{ lead.est_contract_date || '-' }}</div>
                                        </div>
                                        <div class="detail-item">
                                            <label class="detail-label">Est. Start Date</label>
                                            <div class="detail-value">{{ lead.est_start_date || '-' }}</div>
                                        </div>
                                        <div class="detail-item">
                                            <label class="detail-label">Est. Follow Up Date</label>
                                            <div class="detail-value">{{ lead.est_follow_up_date || '-' }}</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Notes & Next Steps Section -->
                                <div class="detail-section">
                                    <h3 class="detail-section-title">Notes & Next Steps</h3>
                                    <div class="detail-grid">
                                        <div class="detail-item full-width">
                                            <label class="detail-label">Meeting Note</label>
                                            <div class="detail-value">{{ lead.meeting_note || '-' }}</div>
                                        </div>
                                        <div class="detail-item full-width">
                                            <label class="detail-label">Next Step</label>
                                            <div class="detail-value">{{ lead.next_step || '-' }}</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- System Information Section -->
                                <div class="detail-section">
                                    <h3 class="detail-section-title">System Information</h3>
                                    <div class="detail-grid">
                                        <div class="detail-item">
                                            <label class="detail-label">Created By</label>
                                            <div class="detail-value">{{ lead.creator?.name || '-' }}</div>
                                        </div>
                                        <div class="detail-item">
                                            <label class="detail-label">Created At</label>
                                            <div class="detail-value">{{ formatDate(lead.created_at) }}</div>
                                        </div>
                                        <div class="detail-item">
                                            <label class="detail-label">Updated At</label>
                                            <div class="detail-value">{{ formatDate(lead.updated_at) }}</div>
                                        </div>
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
import { router, usePage } from '@inertiajs/vue3';
import Menu from '@/Components/Menu.vue';

const props = defineProps({
    lead: { type: Object, required: true }
});

const page = usePage();
const auth = computed(() => page.props.auth?.user);
const adminOpen = ref(false);

const goBack = () => {
    router.get('/leads');
};

const editLead = () => {
    router.get(`/leads/${props.lead.id}/edit`);
};

const logout = () => {
    router.post('/logout');
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<style scoped>
@import '../../css/dashboard.css';

.detail-section {
    margin-bottom: 32px;
    padding-bottom: 24px;
    border-bottom: 1px solid #e5e7eb;
}

.detail-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.detail-section-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
}

.detail-section-title::before {
    content: '';
    width: 4px;
    height: 20px;
    background: #2ecc5e;
    margin-right: 12px;
    border-radius: 2px;
}

.detail-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.detail-item {
    display: flex;
    flex-direction: column;
}

.detail-item.full-width {
    grid-column: 1 / -1;
}

.detail-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 6px;
}

.detail-value {
    font-size: 0.95rem;
    color: #1f2937;
    padding: 10px 12px;
    background: #f9fafb;
    border-radius: 6px;
    border: 1px solid #e5e7eb;
    min-height: 40px;
    display: flex;
    align-items: center;
}

@media (max-width: 768px) {
    .detail-grid {
        grid-template-columns: 1fr;
    }
}
</style>
