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
  };
  products = {
    getAll: () => api.get(environment.productsApi),
    getById: (id: string) => api.get(`${environment.productsApi}/${id}`),
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
