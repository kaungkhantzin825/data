<template>
    <div class="content-card">
        <div class="card-header">
            <div>
                <h2 class="card-title">Lead Management – Outside Leads</h2>
                <p class="card-sub">Manage leads generated outside the system</p>
            </div>
            <div class="card-actions">
                <div v-if="auth?.role === 'Company Super Admin' || auth?.is_admin" class="select-wrap" style="margin-right: 8px;">
                    <select v-model="filters.created_by" class="filter-select" style="min-width: 150px;" @change="$emit('apply')">
                        <option value="">All Users</option>
                        <option v-for="user in availableUsers" :key="user.id" :value="user.id">{{ user.name }}</option>
                    </select>
                </div>
                <button class="btn-outline-green" @click="$emit('upload')">
                    <v-icon icon="mdi-upload" size="15" />
                    Upload Lead
                </button>
                <button class="btn-outline-green" @click="$emit('download')">
                    <v-icon icon="mdi-download" size="15" />
                    Download CSV
                </button>
            </div>
        </div>

        <div class="filters-row">
            <div class="filter-group">
                <label class="filter-label">Search</label>
                <input v-model="filters.search" type="text" placeholder="Search..." class="filter-input" />
            </div>
            <div class="filter-group">
                <label class="filter-label">Plan</label>
                <div class="select-wrap">
                    <select v-model="filters.plan" class="filter-select">
                        <option value="">Select...</option>
                        <option v-for="p in availablePlans" :key="p" :value="p">{{ p }}</option>
                    </select>
                </div>
            </div>
            <div class="filter-group">
                <label class="filter-label">Business Type</label>
                <div class="select-wrap">
                    <select v-model="filters.biz_type" class="filter-select">
                        <option value="">Select...</option>
                        <option v-for="b in availableBizTypes" :key="b" :value="b">{{ b }}</option>
                    </select>
                </div>
            </div>
            <div class="filter-group">
                <label class="filter-label">Status</label>
                <div class="select-wrap">
                    <select v-model="filters.status" class="filter-select">
                        <option value="">Select...</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>
            </div>
            <div class="filter-btns">
                <button class="btn-search" @click="$emit('apply')">Search</button>
                <button class="btn-reset"  @click="$emit('reset')">Reset</button>
            </div>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th v-if="fields.business_name.is_visible">{{ fields.business_name.label }}</th>
                        <th v-if="fields.contact_name.is_visible">{{ fields.contact_name.label }}</th>
                        <th v-if="fields.last_name.is_visible">{{ fields.last_name.label }}</th>
                        <th v-if="fields.contact_email.is_visible">{{ fields.contact_email.label }}</th>
                        <th v-if="fields.phone.is_visible">{{ fields.phone.label }}</th>
                        <th v-if="fields.secondary_contact_number.is_visible">{{ fields.secondary_contact_number.label }}</th>
                        <th v-if="fields.biz_type.is_visible">{{ fields.biz_type.label }}</th>
                        <th v-if="fields.source.is_visible">{{ fields.source.label }}</th>
                        <th v-if="fields.division.is_visible">{{ fields.division.label }}</th>
                        <th v-if="fields.township.is_visible">{{ fields.township.label }}</th>
                        <th v-if="fields.address.is_visible">{{ fields.address.label }}</th>
                        <th v-if="fields.product.is_visible">{{ fields.product.label }}</th>
                        <th v-if="fields.package.is_visible">{{ fields.package.label }}</th>
                        <th v-if="fields.package_total.is_visible">{{ fields.package_total.label }}</th>
                        <th v-if="fields.discount.is_visible">{{ fields.discount.label }}</th>
                        <th v-if="fields.note.is_visible">{{ fields.note.label }}</th>
                        <th v-if="fields.status.is_visible">{{ fields.status.label }}</th>
                        <th v-if="fields.channel.is_visible">{{ fields.channel.label }}</th>
                        <th v-if="fields.installation_appointment.is_visible">{{ fields.installation_appointment.label }}</th>
                        <th v-if="fields.est_contract_date.is_visible">{{ fields.est_contract_date.label }}</th>
                        <th v-if="fields.est_start_date.is_visible">{{ fields.est_start_date.label }}</th>
                        <th v-if="fields.est_follow_up_date.is_visible">{{ fields.est_follow_up_date.label }}</th>
                        <th v-if="fields.is_referral.is_visible">{{ fields.is_referral.label }}</th>
                        <th v-if="fields.meeting_note.is_visible">{{ fields.meeting_note.label }}</th>
                        <th v-if="fields.next_step.is_visible">{{ fields.next_step.label }}</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(lead, i) in leads.data" :key="lead.id" :class="{stripe: i % 2 === 1}">
                        <td>{{ (leads.from ?? 0) + i }}</td>
                        <td v-if="fields.business_name.is_visible" :style="!lead.business_name || lead.business_name === '-' ? 'color: white;' : ''">{{ lead.business_name || '-' }}</td>
                        <td v-if="fields.contact_name.is_visible" :style="!lead.first_name || lead.first_name === '-' ? 'color: white;' : ''">{{ lead.first_name || '-' }}</td>
                        <td v-if="fields.last_name.is_visible" :style="!lead.last_name || lead.last_name === '-' ? 'color: white;' : ''">{{ lead.last_name || '-' }}</td>
                        <td v-if="fields.contact_email.is_visible" :style="!lead.contact_email || lead.contact_email === '-' ? 'color: white;' : ''">{{ lead.contact_email || '-' }}</td>
                        <td v-if="fields.phone.is_visible" :style="!lead.phone || lead.phone === '-' ? 'color: white;' : ''">{{ lead.phone || '-' }}</td>
                        <td v-if="fields.secondary_contact_number.is_visible" :style="!lead.secondary_contact_number || lead.secondary_contact_number === '-' ? 'color: white;' : ''">{{ lead.secondary_contact_number || '-' }}</td>
                        <td v-if="fields.biz_type.is_visible" :style="!lead.biz_type || lead.biz_type === '-' ? 'color: white;' : ''">{{ lead.biz_type || '-' }}</td>
                        <td v-if="fields.source.is_visible" :style="!lead.source || lead.source === '-' ? 'color: white;' : ''">{{ lead.source || '-' }}</td>
                        <td v-if="fields.division.is_visible" :style="!lead.division || lead.division === '-' ? 'color: white;' : ''">{{ lead.division || '-' }}</td>
                        <td v-if="fields.township.is_visible" :style="!lead.township || lead.township === '-' ? 'color: white;' : ''">{{ lead.township || '-' }}</td>
                        <td v-if="fields.address.is_visible" :style="!lead.address || lead.address === '-' ? 'color: white;' : ''">{{ lead.address || '-' }}</td>
                        <td v-if="fields.product.is_visible" :style="!lead.product || lead.product === '-' ? 'color: white;' : ''">{{ lead.product || '-' }}</td>
                        <td v-if="fields.package.is_visible" :style="!lead.package || lead.package === '-' ? 'color: white;' : ''">{{ lead.package || '-' }}</td>
                        <td v-if="fields.package_total.is_visible" :style="!lead.package_total ? 'color: white;' : ''">{{ lead.package_total ? Number(lead.package_total).toLocaleString() : '-' }}</td>
                        <td v-if="fields.discount.is_visible" :style="!lead.discount ? 'color: white;' : ''">{{ lead.discount ? Number(lead.discount).toLocaleString() : '-' }}</td>
                        <td v-if="fields.note.is_visible" :style="!lead.note || lead.note === '-' ? 'color: white;' : ''">{{ lead.note || '-' }}</td>
                        <td v-if="fields.status.is_visible" :style="!lead.status || lead.status === '-' ? 'color: white;' : ''">{{ lead.status || '-' }}</td>
                        <td v-if="fields.channel.is_visible" :style="!lead.channel || lead.channel === '-' ? 'color: white;' : ''">{{ lead.channel || '-' }}</td>
                        <td v-if="fields.installation_appointment.is_visible" :style="!lead.installation_appointment || lead.installation_appointment === '-' ? 'color: white;' : ''">{{ lead.installation_appointment || '-' }}</td>
                        <td v-if="fields.est_contract_date.is_visible" :style="!lead.est_contract_date || lead.est_contract_date === '-' ? 'color: white;' : ''">{{ lead.est_contract_date || '-' }}</td>
                        <td v-if="fields.est_start_date.is_visible" :style="!lead.est_start_date || lead.est_start_date === '-' ? 'color: white;' : ''">{{ lead.est_start_date || '-' }}</td>
                        <td v-if="fields.est_follow_up_date.is_visible" :style="!lead.est_follow_up_date || lead.est_follow_up_date === '-' ? 'color: white;' : ''">{{ lead.est_follow_up_date || '-' }}</td>
                        <td v-if="fields.is_referral.is_visible">{{ lead.is_referral ? 'Yes' : 'No' }}</td>
                        <td v-if="fields.meeting_note.is_visible" :style="!lead.meeting_note || lead.meeting_note === '-' ? 'color: white;' : ''">{{ lead.meeting_note || '-' }}</td>
                        <td v-if="fields.next_step.is_visible" :style="!lead.next_step || lead.next_step === '-' ? 'color: white;' : ''">{{ lead.next_step || '-' }}</td>
                        <td>
                            <div style="display: flex; gap: 8px;">
                                <button class="btn-edit" @click="$emit('view', lead)" style="background: #3b82f6;">
                                    <v-icon icon="mdi-eye" size="12" />
                                    Detail
                                </button>
                                <button class="btn-edit" @click="$emit('edit', lead)">
                                    <v-icon icon="mdi-pencil" size="12" />
                                    Edit
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!leads.data?.length">
                        <td colspan="27" class="empty-row">No records found.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="pagination-bar">
            <span class="pag-info">
                Showing {{ leads.from ?? 0 }} to {{ leads.to ?? 0 }} of {{ leads.total ?? 0 }} entries
            </span>
            <div class="pag-btns">
                <button class="pag-btn" :disabled="!leads.prev_page_url" @click="$emit('page', leads.current_page - 1)">‹</button>
                <button v-for="p in pagPages" :key="p" class="pag-btn" :class="{active: p === leads.current_page}" @click="$emit('page', p)">{{ p }}</button>
                <button class="pag-btn" :disabled="!leads.next_page_url" @click="$emit('page', leads.current_page + 1)">›</button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    leads: { type: Object, required: true },
    filters: { type: Object, required: true },
    availablePlans: { type: Array, default: () => [] },
    availableBizTypes: { type: Array, default: () => [] },
    availableUsers: { type: Array, default: () => [] }
});

const emit = defineEmits(['upload', 'download', 'apply', 'reset', 'edit', 'view', 'page']);

const page = usePage();
const auth = computed(() => page.props.auth?.user);
const can = (p) => {
    return auth.value?.permissions?.includes(p);
};

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

const pagPages = computed(() => {
    let current = props.leads.current_page;
    let last = props.leads.last_page;
    let delta = 2;
    let left = current - delta;
    let right = current + delta + 1;
    let range = [];
    let rangeWithDots = [];
    let l;
    for (let i = 1; i <= last; i++) {
        if (i == 1 || i == last || i >= left && i < right) range.push(i);
    }
    for (let i of range) {
        if (l) {
            if (i - l === 2) rangeWithDots.push(l + 1);
            else if (i - l !== 1) rangeWithDots.push('...');
        }
        rangeWithDots.push(i);
        l = i;
    }
    return rangeWithDots;
});
</script>
