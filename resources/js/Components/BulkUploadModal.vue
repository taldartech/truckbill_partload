<template>
    <div
        class="modal fade"
        ref="modalEl"
        tabindex="-1"
        aria-hidden="true"
        data-bs-backdrop="static"
    >
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header py-2">
                    <h5 class="modal-title fs-6 fw-bold">
                        <i class="fas fa-file-upload text-primary me-2"></i>
                        {{ title }}
                    </h5>
                    <button
                        type="button"
                        class="btn-close"
                        :disabled="isUploading"
                        @click="closeModal"
                        aria-label="Close"
                    ></button>
                </div>

                <!-- Modal Body -->
                <div class="modal-body">
                    <!-- Before Upload: Instructions & File Selection -->
                    <div v-if="!uploadCompleted">
                        <!-- Step 1: Download Template -->
                        <div class="card bg-light border mb-3">
                            <div class="card-body p-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div>
                                    <div class="fw-bold text-dark">Step 1: Download Sample Template</div>
                                    <div class="text-muted small">
                                        Download the standardized CSV file with the required column headers (compatible with Excel).
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-2"
                                    @click="downloadTemplate"
                                    :disabled="isDownloadingTemplate"
                                >
                                    <i class="fas fa-download"></i>
                                    <span>{{ isDownloadingTemplate ? 'Downloading...' : 'Download Template' }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Column Instructions Table -->
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase text-muted mb-1">
                                Column Information
                            </label>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm align-middle mb-1">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Column Header</th>
                                            <th style="width: 130px;">Requirement</th>
                                            <th v-if="hasDescription">Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(col, idx) in columns" :key="idx">
                                            <td>
                                                <code class="fw-bold">{{ col.label || col.name }}</code>
                                            </td>
                                            <td>
                                                <span v-if="col.required" class="badge bg-danger">
                                                    Required
                                                </span>
                                                <span v-else class="badge bg-secondary">
                                                    Optional
                                                </span>
                                            </td>
                                            <td v-if="hasDescription" class="text-muted small">
                                                {{ col.description || col.syntax || '—' }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="form-text small text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                <strong>Empty Rows:</strong> Full empty rows in your file will be skipped automatically without errors.
                            </div>
                        </div>

                        <!-- Step 2: Choose File -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Step 2: Select CSV or Excel File</label>
                            <input
                                ref="fileInputRef"
                                type="file"
                                class="form-control"
                                accept=".csv, .xlsx, .xls, text/csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel"
                                :disabled="isUploading"
                                @change="onFileSelected"
                            />
                            <div v-if="selectedFile" class="form-text text-success small mt-1 d-flex align-items-center justify-content-between">
                                <span>
                                    <i class="fas fa-check-circle me-1"></i>
                                    Selected: <strong>{{ selectedFile.name }}</strong> ({{ formatFileSize(selectedFile.size) }})
                                </span>
                                <button
                                    type="button"
                                    class="btn btn-link btn-sm text-danger p-0 text-decoration-none"
                                    :disabled="isUploading"
                                    @click="clearFile"
                                >
                                    Remove
                                </button>
                            </div>
                        </div>

                        <!-- Loading & Progress Bar -->
                        <div v-if="isUploading" class="mb-2">
                            <div class="d-flex justify-content-between small text-muted mb-1">
                                <span>{{ uploadStatusText }}</span>
                                <span class="fw-bold">{{ uploadProgress }}%</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div
                                    class="progress-bar progress-bar-striped progress-bar-animated bg-success"
                                    role="progressbar"
                                    :style="{ width: `${uploadProgress}%` }"
                                    :aria-valuenow="uploadProgress"
                                    aria-valuemin="0"
                                    aria-valuemax="100"
                                ></div>
                            </div>
                        </div>
                    </div>

                    <!-- After Upload: Results, Summary & Errors -->
                    <div v-else>
                        <!-- 4 Summary Metric Cards -->
                        <div class="row g-2 mb-3 text-center">
                            <div class="col-6 col-sm-3">
                                <div class="card p-2 bg-light border">
                                    <div class="small text-muted fw-semibold">Total Rows</div>
                                    <div class="fs-4 fw-bold text-dark">{{ uploadResult?.summary?.total ?? 0 }}</div>
                                </div>
                            </div>
                            <div class="col-6 col-sm-3">
                                <div class="card p-2 border-success-subtle bg-success-subtle">
                                    <div class="small text-success fw-semibold">Successful</div>
                                    <div class="fs-4 fw-bold text-success">{{ uploadResult?.summary?.successful ?? 0 }}</div>
                                </div>
                            </div>
                            <div class="col-6 col-sm-3">
                                <div class="card p-2 border-danger-subtle bg-danger-subtle">
                                    <div class="small text-danger fw-semibold">Failed</div>
                                    <div class="fs-4 fw-bold text-danger">{{ uploadResult?.summary?.failed ?? 0 }}</div>
                                </div>
                            </div>
                            <div class="col-6 col-sm-3">
                                <div class="card p-2 bg-light border">
                                    <div class="small text-secondary fw-semibold">Skipped (Empty)</div>
                                    <div class="fs-4 fw-bold text-secondary">{{ uploadResult?.summary?.skipped ?? 0 }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Status Alert Banner -->
                        <div
                            v-if="uploadResult?.summary?.failed === 0"
                            class="alert alert-success d-flex align-items-center gap-2 mb-3 py-2"
                        >
                            <i class="fas fa-check-circle fs-5"></i>
                            <div>
                                All <strong>{{ uploadResult?.summary?.successful }}</strong> record(s) were imported successfully!
                            </div>
                        </div>

                        <div
                            v-else
                            class="alert alert-warning d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 py-2"
                        >
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-exclamation-triangle fs-5"></i>
                                <span>
                                    {{ uploadResult?.summary?.successful }} imported, <strong>{{ uploadResult?.summary?.failed }}</strong> row(s) had errors.
                                </span>
                            </div>
                            <button
                                v-if="uploadResult?.errors?.length"
                                type="button"
                                class="btn btn-outline-dark btn-sm"
                                @click="downloadErrorReport"
                            >
                                <i class="fas fa-download me-1"></i>
                                Export Errors CSV
                            </button>
                        </div>

                        <!-- Row-Wise Error Table -->
                        <div v-if="uploadResult?.errors?.length" class="card border mb-2">
                            <div class="card-header bg-light py-2">
                                <span class="fw-bold small text-danger text-uppercase">
                                    <i class="fas fa-exclamation-circle me-1"></i>
                                    Row Errors ({{ uploadResult.errors.length }})
                                </span>
                            </div>
                            <div class="table-responsive" style="max-height: 240px; overflow-y: auto;">
                                <table class="table table-bordered table-hover table-sm align-middle mb-0">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th style="width: 80px;" class="text-center">Row</th>
                                            <th style="width: 140px;">Field</th>
                                            <th style="width: 160px;">Provided Value</th>
                                            <th>Error Reason</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(err, idx) in uploadResult.errors" :key="idx">
                                            <td class="text-center font-monospace">Row {{ err.row }}</td>
                                            <td class="fw-bold font-monospace small">{{ err.field }}</td>
                                            <td class="text-break small">
                                                <span v-if="err.value && err.value.trim()">"{{ err.value }}"</span>
                                                <span v-else class="text-muted font-italic">[Empty]</span>
                                            </td>
                                            <td class="text-danger small">{{ err.message }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="modal-footer py-2">
                    <template v-if="!uploadCompleted">
                        <button
                            type="button"
                            class="btn btn-secondary"
                            :disabled="isUploading"
                            @click="closeModal"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            class="btn btn-primary"
                            :disabled="!selectedFile || isUploading"
                            @click="submitUpload"
                        >
                            <i v-if="!isUploading" class="fas fa-upload me-1"></i>
                            <span v-else class="spinner-border spinner-border-sm me-1" role="status"></span>
                            {{ isUploading ? 'Uploading...' : 'Upload' }}
                        </button>
                    </template>
                    <template v-else>
                        <button
                            type="button"
                            class="btn btn-outline-secondary me-auto"
                            @click="resetToUploadAnother"
                        >
                            <i class="fas fa-redo me-1"></i>
                            Upload Another
                        </button>
                        <button
                            type="button"
                            class="btn btn-primary"
                            @click="closeModal"
                        >
                            Done
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Bulk Upload',
    },
    uploadUrl: {
        type: String,
        required: true,
    },
    templateDownloadUrl: {
        type: String,
        default: '',
    },
    templateFileName: {
        type: String,
        default: 'bulk_upload_template.csv',
    },
    columns: {
        type: Array,
        default: () => [
            {
                name: 'name',
                label: 'Name',
                required: true,
                description: 'Name',
            },
        ],
    },
    sampleData: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['update:show', 'close', 'success']);

const hasDescription = computed(() => {
    return props.columns.some((c) => c.description || c.syntax);
});

const modalEl = ref(null);
let bsModal = null;

const fileInputRef = ref(null);
const selectedFile = ref(null);
const isUploading = ref(false);
const uploadProgress = ref(0);
const uploadStatusText = ref('Uploading...');
const isDownloadingTemplate = ref(false);

const uploadCompleted = ref(false);
const uploadResult = ref(null);
const anySuccess = ref(false);

onMounted(() => {
    if (modalEl.value && typeof bootstrap !== 'undefined') {
        bsModal = new bootstrap.Modal(modalEl.value, {
            backdrop: 'static',
            keyboard: true,
        });

        modalEl.value.addEventListener('hidden.bs.modal', onModalHidden);
    }
});

onUnmounted(() => {
    if (modalEl.value) {
        modalEl.value.removeEventListener('hidden.bs.modal', onModalHidden);
    }
    if (bsModal) {
        bsModal.dispose();
        bsModal = null;
    }
});

const onModalHidden = () => {
    emit('update:show', false);
    emit('close');
    if (anySuccess.value) {
        anySuccess.value = false;
        router.reload();
    }
};

watch(
    () => props.show,
    (isOpen) => {
        if (isOpen) {
            anySuccess.value = false;
            resetState();
            bsModal?.show();
        } else {
            bsModal?.hide();
        }
    }
);

const closeModal = () => {
    if (isUploading.value) return;
    bsModal?.hide();
};

const resetState = () => {
    selectedFile.value = null;
    isUploading.value = false;
    uploadProgress.value = 0;
    uploadStatusText.value = 'Uploading...';
    uploadCompleted.value = false;
    uploadResult.value = null;
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
};

const resetToUploadAnother = () => {
    resetState();
};

const onFileSelected = (event) => {
    const files = event.target.files;
    if (files && files.length > 0) {
        const file = files[0];
        const ext = file.name.split('.').pop().toLowerCase();
        if (ext !== 'csv' && ext !== 'xlsx' && ext !== 'xls') {
            alert('Please select a valid CSV or Excel file (.csv, .xlsx, .xls).');
            clearFile();
            return;
        }
        if (file.size > 5 * 1024 * 1024) {
            alert('The file size exceeds 5MB limit.');
            clearFile();
            return;
        }
        selectedFile.value = file;
    }
};

const clearFile = () => {
    selectedFile.value = null;
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
};

const formatFileSize = (bytes) => {
    if (!bytes || bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const downloadTemplate = async () => {
    if (props.templateDownloadUrl) {
        try {
            isDownloadingTemplate.value = true;
            window.location.href = props.templateDownloadUrl;
            setTimeout(() => {
                isDownloadingTemplate.value = false;
            }, 1000);
            return;
        } catch (e) {
            console.error('Download error:', e);
        } finally {
            isDownloadingTemplate.value = false;
        }
    }

    // Client-side fallback
    const headers = props.columns.map((c) => c.label || c.name);
    let csvContent = '\uFEFF' + headers.join(',') + '\n';
    if (props.sampleData && props.sampleData.length > 0) {
        props.sampleData.forEach((row) => {
            const rowValues = Array.isArray(row)
                ? row
                : props.columns.map((c) => row[c.name] ?? '');
            csvContent += rowValues.map((v) => `"${String(v).replace(/"/g, '""')}"`).join(',') + '\n';
        });
    }

    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.setAttribute('href', url);
    link.setAttribute('download', props.templateFileName || 'template.csv');
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
};

const submitUpload = async () => {
    if (!selectedFile.value || isUploading.value) return;

    isUploading.value = true;
    uploadProgress.value = 15;
    uploadStatusText.value = 'Uploading file...';

    const formData = new FormData();
    formData.append('file', selectedFile.value);

    try {
        const response = await axios.post(props.uploadUrl, formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
            onUploadProgress: (progressEvent) => {
                if (progressEvent.total) {
                    const percent = Math.round((progressEvent.loaded * 80) / progressEvent.total);
                    uploadProgress.value = Math.max(15, percent);
                    if (percent >= 80) {
                        uploadStatusText.value = 'Processing and saving records...';
                    }
                }
            },
        });

        uploadProgress.value = 100;
        uploadStatusText.value = 'Completed!';

        setTimeout(() => {
            isUploading.value = false;
            uploadCompleted.value = true;
            uploadResult.value = response.data;

            if (response.data.summary?.successful > 0) {
                anySuccess.value = true;
                emit('success', response.data);
            }
        }, 300);
    } catch (error) {
        isUploading.value = false;
        const msg = error.response?.data?.message || error.message || 'An error occurred during file upload.';
        uploadCompleted.value = true;
        uploadResult.value = {
            success: false,
            summary: {
                total: 0,
                successful: 0,
                failed: 1,
                skipped: 0,
            },
            errors: [
                {
                    row: 1,
                    field: 'Upload Error',
                    value: selectedFile.value?.name || '',
                    message: msg,
                },
            ],
            message: msg,
        };
    }
};

const downloadErrorReport = () => {
    if (!uploadResult.value?.errors?.length) return;

    const headers = ['Row', 'Field', 'Provided Value', 'Error Description'];
    let csvContent = '\uFEFF' + headers.join(',') + '\n';

    uploadResult.value.errors.forEach((err) => {
        const row = [err.row, err.field, err.value ?? '', err.message ?? ''];
        csvContent += row.map((v) => `"${String(v).replace(/"/g, '""')}"`).join(',') + '\n';
    });

    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.setAttribute('href', url);
    link.setAttribute('download', `bulk_upload_errors_${Date.now()}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
};
</script>
