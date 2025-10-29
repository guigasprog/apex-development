import { Component, EventEmitter, Output } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-buy-dialog',
  standalone: true,
  imports: [CommonModule],
  template: `
    <div class="dialog-backdrop" (click)="close()"></div>
    <div class="dialog-content">
      <h3>O que você deseja fazer?</h3>
      <p>Selecione uma opção para continuar sua compra.</p>

      <div class="dialog-actions">
        <button class="btn btn-secondary" (click)="onAddToCart()">
          <i class="fas fa-cart-plus"></i> Adicionar ao Carrinho
        </button>
        <button class="btn btn-primary" (click)="onBuyNow()">
          <i class="fas fa-bolt"></i> Comprar Agora
        </button>
      </div>

      <button class="btn-close" (click)="close()" aria-label="Fechar">X</button>
    </div>
  `,
  styleUrls: ['./buy-dialog.component.scss']
})
export class BuyDialogComponent {
  // Evento que avisa o componente pai sobre a decisão
  @Output() decision = new EventEmitter<'buyNow' | 'addToCart' | 'close'>();

  onBuyNow() {
    this.decision.emit('buyNow');
  }

  onAddToCart() {
    this.decision.emit('addToCart');
  }

  close() {
    this.decision.emit('close');
  }
}
