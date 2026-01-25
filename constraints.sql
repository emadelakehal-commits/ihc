  ADD CONSTRAINT `lkp_category_parent_code_foreign` FOREIGN KEY (`parent_code`) REFERENCES `lkp_category` (`category_code`) ON DELETE CASCADE;
  ADD CONSTRAINT `lkp_category_translation_category_code_foreign` FOREIGN KEY (`category_code`) REFERENCES `lkp_category` (`category_code`) ON DELETE CASCADE,
  ADD CONSTRAINT `lkp_category_translation_language_foreign` FOREIGN KEY (`language`) REFERENCES `lkp_language` (`code`);
  ADD CONSTRAINT `main_product_translation_ibfk_2` FOREIGN KEY (`language`) REFERENCES `lkp_language` (`code`),
  ADD CONSTRAINT `main_product_translation_language_foreign` FOREIGN KEY (`language`) REFERENCES `lkp_language` (`code`),
  ADD CONSTRAINT `main_product_translation_main_product_code_foreign` FOREIGN KEY (`main_product_code`) REFERENCES `main_product` (`main_product_code`) ON DELETE CASCADE;
  ADD CONSTRAINT `FK_product_attribute_value_attribute` FOREIGN KEY (`attribute_name`) REFERENCES `lkp_attribute` (`name`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_attribute_value_ibfk_1` FOREIGN KEY (`product_code`) REFERENCES `product` (`product_code`) ON DELETE CASCADE;
  ADD CONSTRAINT `product_category_category_code_foreign` FOREIGN KEY (`category_code`) REFERENCES `lkp_category` (`category_code`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_category_product_code_foreign` FOREIGN KEY (`product_code`) REFERENCES `product` (`product_code`) ON DELETE CASCADE;
  ADD CONSTRAINT `FK_product_delivery_domain` FOREIGN KEY (`domain_id`) REFERENCES `domain` (`code`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_delivery_ibfk_1` FOREIGN KEY (`product_code`) REFERENCES `product` (`product_code`) ON DELETE CASCADE;
  ADD CONSTRAINT `product_document_ibfk_1` FOREIGN KEY (`product_code`) REFERENCES `product` (`product_code`) ON DELETE CASCADE;
