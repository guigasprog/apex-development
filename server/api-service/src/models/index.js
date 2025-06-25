const sequelize = require('../config/database');
const Categoria = require('./Categoria');
const Cliente = require('./Cliente');
const Endereco = require('./Endereco');
const Estoque = require('./Estoque');
const ImagemProduto = require('./ImagemProduto');
const Pedido = require('./Pedido');
const PedidoProduto = require('./PedidoProduto');
const Produto = require('./Produto');

Cliente.belongsTo(Endereco, { foreignKey: 'endereco_id', as: 'endereco' });
Endereco.hasOne(Cliente, { foreignKey: 'endereco_id' });

Categoria.hasMany(Produto, { foreignKey: 'categoria_id' });
Produto.belongsTo(Categoria, { foreignKey: 'categoria_id', as: 'categoria' });

Produto.hasMany(Estoque, { foreignKey: 'produto_id', as: 'estoques' });
Estoque.belongsTo(Produto, { foreignKey: 'produto_id' });

Produto.hasMany(ImagemProduto, { foreignKey: 'produto_id', as: 'imagens' });
ImagemProduto.belongsTo(Produto, { foreignKey: 'produto_id' });

Cliente.hasMany(Pedido, { foreignKey: 'cliente_id' });
Pedido.belongsTo(Cliente, { foreignKey: 'cliente_id', as: 'cliente' });

Pedido.belongsToMany(Produto, { through: PedidoProduto, foreignKey: 'pedido_id', as: 'produtos' });
Produto.belongsToMany(Pedido, { through: PedidoProduto, foreignKey: 'produto_id' });


const initDb = async () => {
  try {
    console.log('Modelos sincronizados com o banco de dados.');
  } catch (error) {
    console.error('Erro ao sincronizar modelos:', error);
  }
};

module.exports = {
  sequelize,
  initDb,
  Categoria,
  Cliente,
  Endereco,
  Estoque,
  ImagemProduto,
  Pedido,
  PedidoProduto,
  Produto,
};