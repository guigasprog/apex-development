import { Injectable } from '@angular/core';
import axios from 'axios';
import { environment } from '../../../environments/environment';
import { authStore } from '../state/auth.store';

const api = axios.create();

api.interceptors.request.use((config) => {
  const token = authStore.getValue().token;
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

@Injectable({ providedIn: 'root' })
export class ApiService {
  auth = {
    login: (credentials: any) =>
      api.post(`${environment.authApi}/login`, credentials),
    register: (userData: any) =>
      api.post(`${environment.authApi}/register`, userData),
    forgotPassword: (email: string) =>
      api.post(`${environment.authApi}/forgot-password`, { email }),
    verifyCode: (email: string, code: string) =>
      api.post(`${environment.authApi}/verify-code`, { email, code }),
    resetPassword: (resetData: any) =>
      api.post(`${environment.authApi}/reset-password`, resetData),
  };
  products = {
    getAll: () => api.get(environment.productsApi),
    getById: (id: string) => api.get(`${environment.productsApi}/${id}`),
    trackView: (id: string) =>
      api.post(`${environment.productsApi}/${id}/track-view`),
    getSearchSuggestions: (query: string, id?: number) =>
      api.get(
        `${environment.productsApi}/search/suggestions?q=${query}&id=${id}`
      ),
  };
  orders = {
    create: (orderData: any) => api.post(environment.ordersApi, orderData),
  };
  shipping = {
    calculate: (shippingData: any) =>
      api.post(`${environment.shippingApi}/calculate`, shippingData),
  };
  viacep = {
    get: (cep: string) => axios.get(`https://viacep.com.br/ws/${cep}/json/`),
  };
}
