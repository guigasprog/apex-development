const { DataTypes } = require('sequelize');
const sequelize = require('../config/database');

const Endereco = sequelize.define('enderecos', {
  idEndereco: {
    type: DataTypes.INTEGER,
    autoIncrement: true,
    primaryKey: true
  },
  logradouro: { type: DataTypes.STRING(100) },
  numero: { type: DataTypes.STRING(10) },
  complemento: { type: DataTypes.STRING(50) },
  bairro: { type: DataTypes.STRING(50) },
  cidade: { type: DataTypes.STRING(50) },
  estado: { type: DataTypes.STRING(2) },
  cep: { type: DataTypes.STRING(9) }
}, {
  tableName: 'enderecos',
  timestamps: false
});

module.exports = Endereco;