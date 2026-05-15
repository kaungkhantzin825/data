<template>
    <div class="content-card" style="padding:0;">
        <div class="card-header pb-2" style="padding: 24px 24px 20px 24px; border-bottom: 1px solid #e5e7eb;">
            <div>
                <h2 class="card-title" style="color:#2ecc5e;">Upload Lead</h2>
                <p class="card-sub">Upload Excel or CSV files to add leads</p>
            </div>
            <div class="card-actions">
                <button class="btn-outline-green" @click="$emit('download')">
                    <v-icon icon="mdi-download" size="15" /> Download CSV
                </button>
                <button class="btn-solid-green" @click="$emit('create')">
                    Add New
                </button>
            </div>
        </div>

        <div style="padding: 24px;">
            <div class="rules-alert">
                <div class="rules-icon"><v-icon icon="mdi-alert-circle" color="#ef4444" size="20" /></div>
                <div>
                    <div class="rules-title">Rules:</div>
                    <div class="rules-text">
                        Required columns: <strong>phone</strong>, <strong>first_name</strong>, <strong>last_name</strong>, <strong>business_name</strong>.
                        Optional: <em>biz_type, source, division, township, address, product, package, package_total, discount, note, status, channel, est_contract_date, est_start_date, is_referral, meeting_note, next_step</em>.
                        Download the <a href="/sample_leads.csv" download class="rules-link">Sample CSV File</a> and fill it in — column names must match exactly.
                    </div>
                </div>
            </div>

            <div style="margin-top: 24px;">
                <label style="font-size: 0.85rem; font-weight: 500; color: #374151;">File <span style="color:#ef4444">*</span></label>
                <div class="upload-dropzone" @click.stop="triggerUpload">
                    <v-icon icon="mdi-plus" size="32" color="#6b7280" />
                    <div class="upload-title">Click to upload</div>
                    <div class="upload-sub">Excel file (.xlsx, .xls, .csv) (max 2MB)</div>
                    <input type="file" ref="fileInput" @change="handleFileUpload" accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel" class="upload-input" style="display: none;" />
                </div>
                <div v-if="fileName" style="margin-top: 10px; font-size: 0.85rem; color: #2ecc5e;">
                    Selected File: <strong>{{ fileName }}</strong>
                </div>
            </div>

            <div style="margin-top: 24px;">
                <label class="ref-label upload-check" style="color: #4b5563 !important;">
                    <input type="checkbox" v-model="updateExisting" style="accent-color: #2ecc5e;" /> Update existing leads when phone number matches
                </label>
                <p style="font-size: 0.75rem; color: #9ca3af; margin-top: 4px; margin-left: 24px;">
                    <span v-if="updateExisting">✓ Checked: Existing leads with same phone will be updated</span>
                    <span v-else">✗ Unchecked: All rows will be created as new leads (allows duplicate phone numbers)</span>
                </p>
            </div>

            <div class="form-actions" style="margin-top: 30px;">
                <button type="button" class="btn-form-cancel" @click="$emit('cancel')">Cancel</button>
                <button type="button" class="btn-form-submit" @click="submit">Upload</button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const emit = defineEmits(['upload', 'download', 'create', 'cancel']);
const fileInput = ref(null);
const fileName = ref('');
const file = ref(null);
const updateExisting = ref(false); // Default to false - unchecked

const triggerUpload = (e) => {
    e?.stopPropagation();
    if (fileInput.value) {
        fileInput.value.click();
    }
};

const handleFileUpload = (e) => {
    e?.stopPropagation();
    const selectedFile = e.target.files[0];
    if (selectedFile) {
        file.value = selectedFile;
        fileName.value = selectedFile.name;
    }
};

const submit = () => {
    if (!file.value) return;
    // Pass the actual checkbox value
    emit('upload', { file: file.value, updateExisting: updateExisting.value });
    // Reset file input after upload
    file.value = null;
    fileName.value = '';
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};
</script>
