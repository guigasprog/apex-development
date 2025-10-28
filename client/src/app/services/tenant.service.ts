import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { BehaviorSubject, Observable, tap } from 'rxjs';
import { Title } from '@angular/platform-browser';
import { environment } from '../../environments/environment';

// Interfaces para tipagem dos dados da API
export interface ColorDetail {
  name: string; label: string; hex_light: string | null; hex_dark: string | null;
}
export interface FontDetail {
  name: string; label: string; import_url: string;
}
export interface HoverEffectDetail {
  name: string; label: string; css_code: string;
}
export interface TenantTheme {
  nome_loja: string; url_logo: string; background_mode: 'light' | 'dark';
  primary_color: ColorDetail; secondary_color: ColorDetail; font_ui: FontDetail;
  has_box_shadow: 0 | 1; border_radius_px: number; hover_effect: HoverEffectDetail;
}

@Injectable({
  providedIn: 'root'
})
export class TenantService {
  private tenantThemeSubject = new BehaviorSubject<TenantTheme | null>(null);
  public tenantTheme$ = this.tenantThemeSubject.asObservable();
  public readonly apiBaseUrl = environment.tenantsApi;
  private loadedFontUrls = new Set<string>();

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
    const isDark = theme.background_mode === 'dark';
    const currentThemeMode = isDark ? 'hex_dark' : 'hex_light';

    this.titleService.setTitle(theme.nome_loja);
    const faviconLink = document.getElementById('favicon') as HTMLLinkElement;
    if (faviconLink && theme.url_logo) {
      faviconLink.href = `${environment.imageDB}/${theme.url_logo}`;
    }
    console.log(`Favicon set to: ${faviconLink?.href}`);

    const primaryColorHex = theme.primary_color[currentThemeMode] || (isDark ? '#FFFFFF' : '#000000');
    const secondaryColorHex = theme.secondary_color[currentThemeMode] || (isDark ? '#CCCCCC' : '#555555');
    root.style.setProperty('--primary-color', primaryColorHex);
    root.style.setProperty('--secondary-color', secondaryColorHex);

    this.loadFontIfNeeded(theme.font_ui)

    root.style.setProperty('--font-family', theme.font_ui.name || 'Inter');

    root.style.setProperty('--border-radius', `${theme.border_radius_px}px`);
    document.body.classList.toggle('dark-theme', isDark);

    const shadowValue = 'rgba(0, 0, 0, 0.4)';
    const shadow = theme.has_box_shadow == 1 ? `0 4px 12px 0 ${shadowValue}` : 'none';
    root.style.setProperty('--card-shadow', shadow);

    this.injectHoverCss(theme.hover_effect);
  }

  private loadFontIfNeeded(font: FontDetail): void {
    if (font && font.import_url && !this.loadedFontUrls.has(font.import_url)) {
      const link = document.createElement('link');
      link.href = font.import_url;
      link.rel = 'stylesheet';
      document.head.appendChild(link);
      this.loadedFontUrls.add(font.import_url);
      console.log(`Dynamically loaded font: ${font.name}`);
    }
  }

  // Injeta o CSS do hover effect dinamicamente
  private injectHoverCss(hoverEffect: HoverEffectDetail): void {
      const styleId = 'dynamic-hover-style';
      let styleElement = document.getElementById(styleId) as HTMLStyleElement;

      // Cria a tag <style> se ela não existir
      if (!styleElement) {
          styleElement = document.createElement('style');
          styleElement.id = styleId;
          document.head.appendChild(styleElement);
      }

      // Define o conteúdo CSS (garante que substitua o anterior)
      styleElement.textContent = hoverEffect.css_code || '';
  }
}
