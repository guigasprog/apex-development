import { Routes } from '@angular/router';

import { MainLayoutComponent } from './layout/main-layout/main-layout.component';
import { HomeComponent } from './features/home/home.component';
import { LoginComponent } from './features/auth/login/login.component';
import { ProductDetailComponent } from './features/products/product-detail/product-detail.component';

export const routes: Routes = [
  {
    path: '',
    component: MainLayoutComponent,
    children: [
      { path: '', component: HomeComponent, title: 'Vibe Vault - Início' },
      {
        path: 'product/:id',
        component: ProductDetailComponent,
        title: 'Detalhes do Produto',
      },
    ],
  },
  {
    path: 'login',
    component: LoginComponent,
    title: 'Vibe Vault - Login',
  },
  { path: '**', redirectTo: '', pathMatch: 'full' },
];
