import { Routes } from '@angular/router';
import { SearchComponent } from './pages/search/search.component';
import { ProductDetailComponent } from './pages/product-detail/product-detail.component';
import { ProductListComponent } from './pages/product-list/product-list.component';

export const routes: Routes = [
  {
    path: '',
    component: ProductListComponent,
    pathMatch: 'full'
  },
  { path: 'search', component: SearchComponent},
  {
    path: 'product/:id', // Ex: /product/123
    component: ProductDetailComponent
  },
];
