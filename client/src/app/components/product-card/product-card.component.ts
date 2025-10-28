import { Component, Input } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Observable } from 'rxjs';
import { TenantService, TenantTheme } from '../../services/tenant.service';
import { Product } from '../../models/product.model';
import { environment } from '../../../environments/environment';

@Component({
  selector: 'app-product-card',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './product-card.component.html',
  styleUrls: ['./product-card.component.scss']
})
export class ProductCardComponent {
  @Input() product: Product | null = null;

  theme$: Observable<TenantTheme | null>;
  apiImage: string = environment.imageDB;

  constructor(private tenantService: TenantService) {
    this.theme$ = this.tenantService.tenantTheme$;
  }

  getHoverClass(theme: TenantTheme | null): string {
    if (!theme || !theme.hover_effect || theme.hover_effect.name === 'none') {
      return '';
    }
    return `hover-effect-${theme.hover_effect.name}`;
  }

  getButtonHoverClass(theme: TenantTheme | null): string {
     if (!theme || !theme.hover_effect || theme.hover_effect.name === 'none') {
       return '';
     }
     return theme.hover_effect.name === 'default'
            ? 'hover-effect-default-button'
            : `hover-effect-${theme.hover_effect.name}`;
  }
}
