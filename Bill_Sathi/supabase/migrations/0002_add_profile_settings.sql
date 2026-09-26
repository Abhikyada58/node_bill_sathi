
alter table profiles
  add column if not exists shop_name varchar(150),
  add column if not exists shop_mobile varchar(20),
  add column if not exists shop_address text,
  add column if not exists shop_state varchar(100),
  add column if not exists shop_gstin varchar(15),
  add column if not exists shop_pan varchar(10),
  add column if not exists shop_msme_no varchar(50),
  add column if not exists bank_name varchar(150),
  add column if not exists bank_account_no varchar(50),
  add column if not exists bank_account_type varchar(50),
  add column if not exists bank_ifsc varchar(20);
