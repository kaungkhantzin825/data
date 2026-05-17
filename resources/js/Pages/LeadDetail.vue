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
                                        <div v-if="fields.business_name.is_visible" class="detail-item">
                                            <label class="detail-label">{{ fields.business_name.label }}</label>
                                            <div class="detail-value">{{ lead.business_name || '-' }}</div>
                                        </div>
                                        
                                        <div v-if="fields.contact_name.is_visible" class="detail-item">
                                            <label class="detail-label">{{ fields.contact_name.label }}</label>
                                            <div class="detail-value">{{ lead.first_name || '-' }}</div>
                                        </div>
                                        <div v-if="fields.last_name.is_visible" class="detail-item">
                                            <label class="detail-label">{{ fields.last_name.label }}</label>
                                            <div class="detail-value">{{ lead.last_name || '-' }}</div>
                                        </div>
                                        <div v-if="fields.contact_email.is_visible" class="detail-item">
                                            <label class="detail-label">{{ fields.contact_email.label }}</label>
                                            <div class="detail-value">{{ lead.contact_email || '-' }}</div>
                                        </div>
                                        <div v-if="fields.phone.is_visible" class="detail-item">
                                            <label class="detail-label">{{ fields.phone.label }}</label>
                                            <div class="detail-value">{{ lead.phone || '-' }}</div>
                                        </div>
                                        <div v-if="fields.secondary_contact_number.is_visible" class="detail-item">
                                            <label class="detail-label">{{ fields.secondary_contact_number.label }}</label>
                                            <div class="detail-value">{{ lead.secondary_contact_number || '-' }}</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Location & Business Section -->
                                <div class="detail-section">
                                    <h3 class="detail-section-title">Location & Business Information</h3>
                                    <div class="detail-grid">
                                        <div v-if="fields.biz_type.is_visible" class="detail-item">
                                            <label class="detail-label">{{ fields.biz_type.label }}</label>
                                            <div class="detail-value">{{ lead.biz_type || '-' }}</div>
                                        </div>
                                        <div v-if="fields.source.is_visible" class="detail-item">
                                            <label class="detail-label">{{ fields.source.label }}</label>
                                            <div class="detail-value">{{ lead.source || '-' }}</div>
                                        </div>
                                        <div v-if="fields.division.is_visible" class="detail-item">
                                            <label class="detail-label">{{ fields.division.label }}</label>
                                            <div class="detail-value">{{ lead.division || '-' }}</div>
                                        </div>
                                        <div v-if="fields.township.is_visible" class="detail-item">
                                            <label class="detail-label">{{ fields.township.label }}</label>
                                            <div class="detail-value">{{ lead.township || '-' }}</div>
                                        </div>
                                        <div v-if="fields.address.is_visible" class="detail-item full-width">
                                            <label class="detail-label">{{ fields.address.label }}</label>
                                            <div class="detail-value">{{ lead.address || '-' }}</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Product & Pricing Section -->
                                <div class="detail-section">
                                    <h3 class="detail-section-title">Product & Pricing</h3>
                                    <div class="detail-grid">
                                        <div v-if="fields.product.is_visible" class="detail-item">
                                            <label class="detail-label">{{ fields.product.label }}</label>
                                            <div class="detail-value">{{ lead.product || '-' }}</div>
                                        </div>
                                        <div v-if="fields.package.is_visible" class="detail-item">
                                            <label class="detail-label">{{ fields.package.label }}</label>
                                            <div class="detail-value">{{ lead.package || '-' }}</div>
                                        </div>
                                        <div class="detail-item">
                                            <label class="detail-label">Plan</label>
                                            <div class="detail-value">{{ lead.plan || '-' }}</div>
                                        </div>
                                        <div v-if="fields.package_total.is_visible" class="detail-item">
                                            <label class="detail-label">{{ fields.package_total.label }}</label>
                                            <div class="detail-value">{{ lead.package_total ? Number(lead.package_total).toLocaleString() : '-' }}</div>
                                        </div>
                                        <div v-if="fields.discount.is_visible" class="detail-item">
                                            <label class="detail-label">{{ fields.discount.label }}</label>
                                            <div class="detail-value">{{ lead.discount ? Number(lead.discount).toLocaleString() : '-' }}</div>
                                        </div>
                                        <div class="detail-item">
                                            <label class="detail-label">Amount</label>
                                            <div class="detail-value">{{ lead.amount ? Number(lead.amount).toLocaleString() : '-' }}</div>
                                        </div>
                                        <div v-if="fields.note.is_visible" class="detail-item full-width">
                                            <label class="detail-label">{{ fields.note.label }}</label>
                                            <div class="detail-value">{{ lead.note || '-' }}</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Status & Channel Section -->
                                <div class="detail-section">
                                    <h3 class="detail-section-title">Status & Channel</h3>
                                    <div class="detail-grid">
                                        <div v-if="fields.status.is_visible" class="detail-item">
                                            <label class="detail-label">{{ fields.status.label }}</label>
                                            <div class="detail-value">{{ lead.status || '-' }}</div>
                                        </div>
                                        <div v-if="fields.channel.is_visible" class="detail-item">
                                            <label class="detail-label">{{ fields.channel.label }}</label>
                                            <div class="detail-value">{{ lead.channel || '-' }}</div>
                                        </div>
                                        <div v-if="fields.is_referral.is_visible" class="detail-item">
                                            <label class="detail-label">{{ fields.is_referral.label }}</label>
                                            <div class="detail-value">{{ lead.is_referral ? 'Yes' : 'No' }}</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Important Dates Section -->
                                <div class="detail-section">
                                    <h3 class="detail-section-title">Important Dates</h3>
                                    <div class="detail-grid">
                                        <div v-if="fields.installation_appointment.is_visible" class="detail-item">
                                            <label class="detail-label">{{ fields.installation_appointment.label }}</label>
                                            <div class="detail-value">{{ lead.installation_appointment || '-' }}</div>
                                        </div>
                                        <div v-if="fields.est_contract_date.is_visible" class="detail-item">
                                            <label class="detail-label">{{ fields.est_contract_date.label }}</label>
                                            <div class="detail-value">{{ lead.est_contract_date || '-' }}</div>
                                        </div>
                                        <div v-if="fields.est_start_date.is_visible" class="detail-item">
                                            <label class="detail-label">{{ fields.est_start_date.label }}</label>
                                            <div class="detail-value">{{ lead.est_start_date || '-' }}</div>
                                        </div>
                                        <div v-if="fields.est_follow_up_date.is_visible" class="detail-item">
                                            <label class="detail-label">{{ fields.est_follow_up_date.label }}</label>
                                            <div class="detail-value">{{ lead.est_follow_up_date || '-' }}</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Notes & Next Steps Section -->
                                <div class="detail-section">
                                    <h3 class="detail-section-title">Notes & Next Steps</h3>
                                    <div class="detail-grid">
                                        <div v-if="fields.meeting_note.is_visible" class="detail-item full-width">
                                            <label class="detail-label">{{ fields.meeting_note.label }}</label>
                                            <div class="detail-value">{{ lead.meeting_note || '-' }}</div>
                                        </div>
                                        <div v-if="fields.next_step.is_visible" class="detail-item full-width">
                                            <label class="detail-label">{{ fields.next_step.label }}</label>
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

const fields = computed(() => {
    const custom = page.props.auth?.user?.tenant_settings?.form_fields || {};
    let merged = {};
    const defaults = {
    "business_name": {
        "label": "Business Name",
        "is_visible": true
    },
    "contact_name": {
        "label": "First Name",
        "is_visible": true
    },
    "last_name": {
        "label": "Last Name",
        "is_visible": true
    },
    "contact_email": {
        "label": "Contact Email",
        "is_visible": true
    },
    "phone": {
        "label": "Phone Number",
        "is_visible": true
    },
    "secondary_contact_number": {
        "label": "Secondary Contact Number",
        "is_visible": true
    },
    "biz_type": {
        "label": "Business Type",
        "is_visible": true
    },
    "source": {
        "label": "Lead Source",
        "is_visible": true
    },
    "division": {
        "label": "Division",
        "is_visible": true
    },
    "township": {
        "label": "Township",
        "is_visible": true
    },
    "address": {
        "label": "Address",
        "is_visible": true
    },
    "product": {
        "label": "Product",
        "is_visible": true
    },
    "package": {
        "label": "Package",
        "is_visible": true
    },
    "package_total": {
        "label": "Package Total",
        "is_visible": true
    },
    "discount": {
        "label": "Discount",
        "is_visible": true
    },
    "note": {
        "label": "Note",
        "is_visible": true
    },
    "status": {
        "label": "Status",
        "is_visible": true
    },
    "channel": {
        "label": "Channel",
        "is_visible": true
    },
    "installation_appointment": {
        "label": "Installation Appointment",
        "is_visible": true
    },
    "est_contract_date": {
        "label": "Est. Contract Date",
        "is_visible": true
    },
    "est_start_date": {
        "label": "Est. Start Date",
        "is_visible": true
    },
    "est_follow_up_date": {
        "label": "Est. Follow Up Date",
        "is_visible": true
    },
    "is_referral": {
        "label": "Referral ?",
        "is_visible": true
    },
    "meeting_note": {
        "label": "Meeting Note",
        "is_visible": true
    },
    "next_step": {
        "label": "Next Step",
        "is_visible": true
    }
};
    for (let key in defaults) {
        merged[key] = {
            label: custom[key]?.label || defaults[key].label,
            is_visible: custom[key]?.is_visible ?? defaults[key].is_visible
        };
    }
    return merged;
});

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
