import { Routes } from '@angular/router';
import { StorefrontComponent } from './components/storefront/storefront.component';
import { SearchComponent } from './pages/search/search.component';
import { ProductDetailComponent } from './pages/product-detail/product-detail.component';

export const routes: Routes = [
  { path: '', component: StorefrontComponent, pathMatch: 'full' },
  { path: 'search', component: SearchComponent},
  {
    path: 'product/:id', // Ex: /product/123
    component: ProductDetailComponent
  },
];
