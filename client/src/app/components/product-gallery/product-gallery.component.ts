import { Component, Input, OnChanges, SimpleChanges } from '@angular/core';
import { CommonModule } from '@angular/common';

// Interface para as imagens que chegam
interface GalleryImage {
  image_url: string;
  descricao?: string;
}

@Component({
  selector: 'app-product-gallery',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './product-gallery.component.html',
  styleUrls: ['./product-gallery.component.scss']
})
export class ProductGalleryComponent implements OnChanges {
  @Input() images: GalleryImage[] = [];
  @Input() apiImagePrefix: string = '';

  activeImageUrl: string = 'assets/placeholder.png';
  activeImageAlt: string = 'Carregando...';
  activeImageIndex: number = 0;

  // --- LÓGICA DE ZOOM ATUALIZADA ---
  isZooming: boolean = false;
  zoomOrigin: string = 'center center';

  ngOnChanges(changes: SimpleChanges): void {
    if (changes['images'] && this.images && this.images.length > 0) {
      this.selectImage(0);
    } else if (!this.images || this.images.length === 0) {
      this.activeImageUrl = 'assets/placeholder.png';
      this.activeImageAlt = 'Imagem não disponível';
    }
  }

  selectImage(index: number): void {
    if (!this.images || !this.images[index]) return;

    this.activeImageIndex = index;
    this.activeImageUrl = this.apiImagePrefix + this.images[index].image_url;
    this.activeImageAlt = this.images[index].descricao || 'Imagem do Produto';

    // --- ATUALIZADO ---
    // Reseta o zoom se a imagem for trocada
    this.isZooming = false;
    this.zoomOrigin = 'center center'; // Reseta a posição
  }

  // --- MÉTODOS DE ZOOM ATUALIZADOS ---

  /**
   * Ativa ou desativa o modo de zoom ao clicar.
   */
  toggleZoom(): void {
    if (this.images.length === 0) return; // Não faz nada se não houver imagem

    this.isZooming = !this.isZooming;

    // Se estiver desativando o zoom, reseta a origem
    if (!this.isZooming) {
      this.zoomOrigin = 'center center';
    }
  }

  toggleZoomLeave(): void {
    this.isZooming = false;
  }

  /**
   * Calcula a posição do zoom com base no cursor do mouse.
   * Só é executado se o zoom estiver ativo.
   */
  onMouseMove(event: MouseEvent): void {
    // Só atualiza a posição se o zoom estiver ATIVO
    if (!this.isZooming) return;

    const target = event.currentTarget as HTMLElement;
    const xPos = event.offsetX / target.clientWidth;
    const yPos = event.offsetY / target.clientHeight;

    this.zoomOrigin = `${xPos * 100}% ${yPos * 100}%`;
  }
}
