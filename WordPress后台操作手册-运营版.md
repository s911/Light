# WordPress 后台操作手册（运营版）

适用对象：运营、客服、销售支持  
站点类型：舞台灯独立站（B2C + B2B）

---

## 1. 登录与角色权限

- 后台入口：`https://你的域名/wp-admin`
- 建议角色：
  - Administrator：仅技术/负责人
  - Shop Manager：电商与订单管理
  - Editor：页面与博客内容维护
- 要求：
  - 必开强密码 + 2FA
  - 禁止共享管理员账号
- 社交登录（Google/Facebook）：
  - 插件：`Nextend Social Login`
  - 配置路径：`Settings -> Nextend Social Login`
  - 完成 OAuth 配置后，登录/注册页会显示社交登录按钮

---

## 2. 首次初始化（一次性）

1. `Appearance -> Themes` 激活 `Stage Lighting Theme`
2. `Plugins` 激活：
   - `Stage Lighting B2B Quote`
   - `Stage Lighting Site Setup`
3. 打开 `Tools -> Stage Lighting Setup`
4. 点击 `Run Initialization`

系统会自动创建：

- 页面：Home / Products / Solutions / Projects / For Business / About / Blog / Contact 等
- 菜单：主导航和页脚导航
- 商品分类：5个一级类
- 场景标签：7个应用场景
- 商品属性：Power / Application / Control Protocol / Certification
- 示例商品：`Beam Moving Head 350W`

---

## 3. 商品管理（WooCommerce）

## 3.0 Excel 批量导入（动态参数）

路径：`Tools -> Stage Product Importer`

支持文件：

- `.xlsx`（读取第一个工作表）
- `.csv`

列规范（常用）：

- `name`（必填）
- `sku`
- `description`
- `short_description`
- `regular_price`
- `sale_price`
- `stock`
- `categories`
- `tags`
- `download_links`
- `download_items`（格式示例：`Manual::https://...|Certificate::https://...`）
- `video_url`

动态参数列（可无限扩展）：

- 列名格式：`attr:参数名`
- 示例：`attr:Power`、`attr:Beam Angle`、`attr:Control Protocol`
- 一个单元格多个值可用逗号或 `|` 分隔

说明：

- 运营可以直接在 Excel 新增任意 `attr:*` 列，无需改代码。
- 导入时可勾选“按 SKU 更新已有商品”。
- 建议按“两步导入”执行：先点 `Preview Import`，确认数量后再点 `Confirm Import`。
- 若有失败行，可下载 `Error CSV` 修正后重新导入。
- 若要在前台按分组显示下载，优先使用 `download_items` 列。
- 每次确认导入后会自动记录日志，路径：`Tools -> Stage Import Logs`（时间/操作人/文件/结果统计）。

## 3.0.1 产品对比功能（P1）

- 前台会显示 `Add Compare` 按钮（首页/产品列表/产品详情）。
- 顶部导航显示 `Compare (N)` 数量。
- 对比页面路径：`/product-compare`
- 单次最多对比 4 个商品。

## 3.0.2 愿望清单（Wishlist）

- 前台会显示 `Add Wishlist` 按钮（首页/产品列表/产品详情）。
- 顶部导航显示 `Wishlist (N)` 数量。
- 愿望清单页面路径：`/wishlist`
- 可在愿望清单页面一键移除或清空。
- 登录用户的 Wishlist 会自动与账号同步（跨会话保留）。
- 客户在 `My Account` 中可看到 `Wishlist` 入口。

## 3.1 新增商品

路径：`Products -> Add New`

必填建议：

- Product Name（英文）
- Main Image（白底图）+ Gallery（场景图）
- Short Description（3-5行卖点）
- Long Description（参数、应用、售后）
- Price / Stock / SKU
- Category（五大类）
- Tags（应用场景）

## 3.2 商品信息模板建议

- 功率：如 `350W`
- 控制协议：如 `DMX512`
- 光束角/色温/CRI（按设备类型补充）
- 电压与频率
- 认证：`CE/FCC/RoHS/UL`
- 包装尺寸与重量

---

## 4. B2B 询盘管理

## 4.1 入口说明

- 页面入口：`For Business`
- 商品页入口：`Request Bulk Quote`（自动带入产品名）

## 4.2 表单字段（已配置）

- Full Name
- Company Name
- Email Address
- Phone Number
- Country
- Interested Product(s)
- Estimated Quantity
- Project Description
- Upload File

## 4.3 提交后的处理流程

1. 销售邮箱收到询盘通知
2. 客户自动收到回执邮件
3. 后台 `B2B Leads` 自动生成线索记录
4. 运营在 `B2B Leads` 中更新状态：`new/contacted/quoted/won/lost`
5. 在每条线索详情中维护 `Follow-up Date`（建议首次设置为提交后2天内）
6. 可在 `B2B Leads` 列表按状态和跟进日期区间筛选，并使用 `Export CSV` 导出当前筛选结果
7. 优先处理 `Overdue=YES` 的线索
8. 运营在 CRM/表格中登记线索并分配负责人
9. 24小时内首次回复
10. 系统每日会自动发送超期线索提醒邮件到 Sales Email

## 4.4 联系方式配置（后台可改）

路径：`Settings -> Stage B2B Settings`

- Sales Email（询盘收件邮箱）
- WhatsApp Number（仅数字，含国家码）
- WhatsApp Display Text（前台展示文案）
- `Send Reminder Now`（手动立即发送一次超期线索提醒）

说明：

- 页脚联系方式与右下角 WhatsApp 浮动按钮会自动读取上述配置。
- 建议上线前先完成此配置再做验收。

## 4.5 营销设置（社媒/统计/订阅）

路径：`Settings -> Stage Marketing Settings`

- Instagram/Facebook/TikTok/YouTube 链接
- GA4 Measurement ID
- Meta Pixel ID
- Newsletter Popup 开关
- Live Chat（Tawk.to / Tidio / Custom Script）

说明：

- 订阅名单在后台 `Newsletter Subscribers` 菜单查看。
- 前台页脚会展示已配置的社媒链接。

---

## 5. 页面与菜单维护

## 5.1 页面编辑

路径：`Pages -> All Pages`

- 首页：建议用 Elementor 维护模块区块
- For Business：保留表单短代码 `[stage_bulk_quote_form]`
- Projects/Blog：持续更新案例与内容营销

## 5.2 菜单编辑

路径：`Appearance -> Menus`

- 主导航：Home / Products / Solutions / Projects / For Business / About / Blog / Contact
- 页脚：For Business / OEM-ODM / Downloads / Contact

---

## 6. 订单与客户服务

## 6.1 订单处理

路径：`WooCommerce -> Orders`

- 新订单：确认支付、备货、发货
- 异常订单：核对支付状态和地址
- 完成订单：发送物流单号与售后说明

## 6.2 常见客服回复模板（建议）

- 报价时效：24h 内
- 样品政策：说明起订量与样品运费
- 定制能力：OEM/ODM 支持范围

## 6.3 订单追踪入口

- 页面路径：`/order-tracking`
- 页面使用 WooCommerce 标准追踪表单（订单号 + 下单邮箱）。

---

## 7. 内容运营节奏（SEO）

- 每周至少发布 1 篇 Blog
- 每月复盘高流量页面并优化 CTA
- 重点文章类型：
  - Buying Guide
  - Tech Explainer
  - Case Study
  - Industry Trends

## 8. 上线前验收（建议）

- 执行：`bash scripts/site-audit.sh --prod`
- 关注结果：
  - `[FAIL]` 必须修复后再上线
  - `[WARN]` 为提示项（如社交登录回调域名检查），建议人工确认
  - Installation Tutorial

---

## 8. 安全与更新 SOP

- 每周：
  - 检查插件更新和安全告警
  - 先测试站验证，再更新生产站
- 每日：
  - 检查订单、询盘、邮件投递状态
- 每月：
  - 做一次备份恢复演练

---

## 9. 常见问题

1. 看不到询盘按钮  
   - 检查插件 `Stage Lighting B2B Quote` 是否启用

2. 询盘提交后没收到邮件  
   - 检查 SMTP 插件、发件域名 SPF/DKIM

3. 商品页样式错乱  
   - 清除缓存插件 + CDN 缓存后重试

