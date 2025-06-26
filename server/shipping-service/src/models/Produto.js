const { DataTypes } = require("sequelize");
const sequelize = require("../config/database");

const Produto = sequelize.define(
  "produtos",
  {
    id: { type: DataTypes.INTEGER, autoIncrement: true, primaryKey: true },
    preco: { type: DataTypes.DECIMAL(10, 2), allowNull: false },
    peso_kg: { type: DataTypes.DECIMAL(10, 2), allowNull: false },
    comprimento_cm: { type: DataTypes.INTEGER, allowNull: false },
    altura_cm: { type: DataTypes.INTEGER, allowNull: false },
    largura_cm: { type: DataTypes.INTEGER, allowNull: false },
  },
  { tableName: "produtos", timestamps: false }
);

module.exports = { Produto };
