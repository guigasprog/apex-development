require("dotenv").config();
const express = require("express");
const cors = require("cors");
const sequelize = require("./config/database");
const shippingRoutes = require("./routes/shippingRoutes");

require("./models/Produto");

const app = express();
app.use(cors());
app.use(express.json());

sequelize
  .authenticate()
  .then(() => console.log("Shipping-service conectado ao MySQL."))
  .catch((err) => console.error("Erro de conexão com DB:", err));

app.use("/api/shipping", shippingRoutes);

const PORT = process.env.PORT || 4004;
app.listen(PORT, () =>
  console.log(`Serviço de Frete rodando na porta ${PORT}`)
);
