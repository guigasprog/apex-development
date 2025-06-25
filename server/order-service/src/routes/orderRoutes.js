const express = require('express');
const router = express.Router();
const orderController = require('../controllers/orderController');
const authMiddleware = require('../middleware/authMiddleware');

// Todas as rotas de pedido são protegidas
router.post('/', authMiddleware, orderController.createOrder);

module.exports = router;