const express = require("express");
const router = express.Router();
const productController = require("../controllers/productController");
const authMiddleware = require("../middleware/authMiddleware");

router.get("/", productController.getAllProducts);
router.get("/search/suggestions", productController.getSearchSuggestions);
router.post("/search/track", productController.trackSearch);
router.get("/:id", productController.getProductById);
router.post("/:id/track-view", productController.trackProductView);

module.exports = router;
