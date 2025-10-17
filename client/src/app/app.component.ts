import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common'; // <-- 1. IMPORT CommonModule
import { TenantService } from './services/tenant.service';
import { Observable } from 'rxjs';
import { TenantTheme } from './services/tenant.service';

@Component({
  selector: 'app-root',
  standalone: true, // Modern Angular uses standalone components
  imports: [CommonModule], // <-- 2. ADD imports array with CommonModule
  templateUrl: './app.component.html',
  styleUrls: ['./app.component.scss']
})
export class AppComponent implements OnInit {

  theme$: Observable<TenantTheme | null>;
  apiBaseUrl: string;

  constructor(private tenantService: TenantService) {
    this.theme$ = this.tenantService.tenantTheme$;
    this.apiBaseUrl = this.tenantService.apiBaseUrl;
  }

  ngOnInit(): void {
    this.tenantService.loadTenant().subscribe({
      next: (theme) => {
        if (theme) {
          console.log(`Tema da loja "${theme.nome_loja}" carregado com sucesso!`);
        }
      },
      error: (err) => {
        console.error('Falha ao carregar o tema:', err);
        document.body.innerHTML = `<h1>Erro: Loja não encontrada</h1><p>${err.error?.error || err.message}</p>`;
      }
    });
  }
}
