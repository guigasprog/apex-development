require('dotenv').config();
const express = require('express');
const cors = require('cors');
const sequelize = require('./config/database');
const orderRoutes = require('./routes/orderRoutes');

require('./models'); // Garante que os modelos sejam carregados

const app = express();
app.use(cors());
app.use(express.json());

sequelize.authenticate()
  .then(() => console.log('Order-service conectado ao MySQL.'))
  .catch(err => console.error('Não foi possível conectar ao banco de dados:', err));

app.use('/api/orders', orderRoutes);

const PORT = process.env.PORT || 4003;
app.listen(PORT, () => {
  console.log(`Serviço de Pedidos rodando na porta ${PORT}`);
});