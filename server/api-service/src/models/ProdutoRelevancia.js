const { DataTypes } = require("sequelize");
const sequelize = require("../config/database");

const ProdutoRelevancia = sequelize.define(
  "produto_relevancia",
  {
    id: { type: DataTypes.INTEGER, autoIncrement: true, primaryKey: true },
    produto_id: { type: DataTypes.INTEGER, allowNull: true },
    tipo_relevancia: {
      type: DataTypes.ENUM("view", "search"),
      defaultValue: "view",
    },
    texto_busca: { type: DataTypes.STRING, allowNull: true },
  },
  {
    tableName: "produto_relevancia",
    timestamps: true,
    updatedAt: false,
    createdAt: "created_at",
  }
);

module.exports = ProdutoRelevancia;
