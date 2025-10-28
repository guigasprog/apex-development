
import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { TenantService } from './services/tenant.service';
import { RouterOutlet } from '@angular/router';
import { StoreHeaderComponent } from './components/store-header/store-header.component';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [ CommonModule, StoreHeaderComponent, RouterOutlet ],
  template: `
    <app-store-header></app-store-header>
    <router-outlet *ngIf="themeLoaded" />
    <div *ngIf="!themeLoaded && !loadError" class="loading-spinner">Carregando sua loja...</div>
    <div *ngIf="loadError" class="error-message">
      <h1>Erro ao Carregar a Loja</h1>
      <p>{{ errorMessage }}</p>
    </div>
  `,
})
export class AppComponent implements OnInit {

  themeLoaded = false;
  loadError = false;
  errorMessage = '';

  constructor(private tenantService: TenantService) {}

  ngOnInit(): void {
    this.tenantService.loadTenant().subscribe({
      next: (theme) => {
        if (theme) {
          this.themeLoaded = true;
        } else {
          this.loadError = true; this.errorMessage = 'Dados do tema não encontrados.';
        }
      },
      error: (err) => {
        this.loadError = true; this.errorMessage = err.error?.error || err.message || 'API indisponível.';
      }
    });
  }
}
