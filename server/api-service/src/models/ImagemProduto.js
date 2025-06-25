const { DataTypes } = require('sequelize');
const sequelize = require('../config/database');

const ImagemProduto = sequelize.define('imagens_produto', {
  id: {
    type: DataTypes.INTEGER,
    autoIncrement: true,
    primaryKey: true
  },
  produto_id: {
    type: DataTypes.INTEGER,
    allowNull: false
  },
  descricao: {
    type: DataTypes.STRING(255),
    allowNull: false
  },
  image_url: {
    type: DataTypes.STRING(255),
    allowNull: false
  }
}, {
  tableName: 'imagens_produto',
  timestamps: false
});

module.exports = ImagemProduto;