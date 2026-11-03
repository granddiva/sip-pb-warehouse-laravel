import axios from 'axios';

const api = axios.create({
  baseURL: '/api',
  withCredentials: true,
  headers: {
    'X-Requested-With': 'XMLHttpRequest',
    'X-CSRF-TOKEN': window.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
    'Accept': 'application/json',
  },
});

// Update CSRF token dynamically if provided
api.interceptors.request.use((config) => {
  const token = window.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  if (token) {
    config.headers['X-CSRF-TOKEN'] = token;
  }
  return config;
});

export const authApi = {
  login: (username, password, remember = false) => api.post('/login', { username, password, remember }),
  logout: () => api.post('/logout'),
  me: () => api.get('/me'),
};

export const dashboardApi = {
  getStats: () => api.get('/dashboard'),
};

export const documentApi = {
  getAll: (params = {}) => api.get('/documents', { params }),
  getById: (id) => api.get(`/documents/${id}`),
  generateNumber: (params = {}) => api.get('/documents/generate-number', { params }),
  create: (formData) => api.post('/documents', formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  }),
  update: (id, data) => api.put(`/documents/${id}`, data),
  delete: (id) => api.delete(`/documents/${id}`),
  verifyPhysical: (id, data) => api.post(`/documents/${id}/verify-physical`, data),
  relocate: (data) => api.post('/documents/relocate', data),
  exportUrl: '/api/documents/export',
};

export const rackApi = {
  getRacks: () => api.get('/racks'),
  getBoxDocs: (boxId) => api.get(`/boxes/${boxId}/documents`),
  toggleBoxLock: (boxId) => api.post(`/boxes/${boxId}/toggle-lock`),
};

export const borrowingApi = {
  getAll: (params = {}) => api.get('/borrowings', { params }),
  getAvailableDocs: () => api.get('/borrowings/available-docs'),
  borrow: (data) => api.post('/borrowings', data),
  returnDoc: (id, data) => api.post(`/borrowings/${id}/return`, data),
  exportUrl: '/api/borrowings/export',
};

export const attachmentApi = {
  upload: (documentId, formData) => api.post(`/documents/${documentId}/attachments`, formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  }),
  delete: (attachmentId) => api.delete(`/attachments/${attachmentId}`),
};

export const qrCodeApi = {
  getDocument: (id) => api.get(`/qrcodes/document/${id}`),
  getBox: (id) => api.get(`/qrcodes/box/${id}`),
  getBatch: (type = 'boxes') => api.get('/qrcodes/batch', { params: { type } }),
};

export const scannerApi = {
  resolve: (code) => api.get('/scan/resolve', { params: { code } }),
};

export const activityLogApi = {
  getAll: (page = 1) => api.get('/activity-logs', { params: { page } }),
};

export default api;
