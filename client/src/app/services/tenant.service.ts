import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { BehaviorSubject, Observable, tap } from 'rxjs';
import { Title } from '@angular/platform-browser';
import { environment } from '../../environments/environment';

// Interface (no changes needed here)
export interface TenantTheme {
  nome_loja: string;
  url_logo: string;
  background_mode: 'light' | 'dark';
  primary_color: string;
  secondary_color: string;
  font_ui: string;
  has_box_shadow: 0 | 1;
  border_radius_px: number;
  hover_effect: 'default' | 'scale' | 'elevate' | 'glow' | 'none';
}

@Injectable({
  providedIn: 'root'
})
export class TenantService {
  private tenantThemeSubject = new BehaviorSubject<TenantTheme | null>(null);
  public tenantTheme$ = this.tenantThemeSubject.asObservable();
  public readonly apiBaseUrl = environment.tenantsApi;

  constructor(private http: HttpClient, private titleService: Title) {}

  loadTenant(): Observable<TenantTheme> {
    const apiUrl = `${this.apiBaseUrl}/details`;

    return this.http.get<TenantTheme>(apiUrl).pipe(
      tap(theme => {
        this.tenantThemeSubject.next(theme);
        this.applyTheme(theme);
      })
    );
  }

  private applyTheme(theme: TenantTheme): void {
    const root = document.documentElement;

    this.titleService.setTitle(theme.nome_loja);

    const faviconLink = document.getElementById('favicon') as HTMLLinkElement;
    if (faviconLink) {
      faviconLink.href = `${environment.imageDB}/${theme.url_logo}`;
    }

    const colorMapPrimary: {[key: string]: string} = {'default': '#3498db', 'greenlime': '#2ecc71', 'purple': '#8e44ad', 'orange': '#e67e22'};
    const colorMapSecondary: {[key: string]: string} = {'default': '#555', 'red': '#e74c3c', 'yellow': '#f1c40f'};

    root.style.setProperty('--primary-color', colorMapPrimary[theme.primary_color] || colorMapPrimary['default']);
    root.style.setProperty('--secondary-color', colorMapSecondary[theme.secondary_color] || colorMapSecondary['default']);
    root.style.setProperty('--font-family', theme.font_ui || 'Inter');
    root.style.setProperty('--border-radius', `${theme.border_radius_px}px`);
    document.body.classList.toggle('dark-theme', theme.background_mode === 'dark');
    root.style.setProperty('--hover-class', `hover-effect-${theme.hover_effect}`);
    const shadow = theme.has_box_shadow ? '0 4px 12px 0 rgba(0,0,0,0.1)' : 'none';
    root.style.setProperty('--card-shadow', shadow);
  }
}
