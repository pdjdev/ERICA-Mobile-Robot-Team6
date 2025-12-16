<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>상품 관리</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background-color: #f5f7fa; }
        .header { background: white; padding: 20px 0; box-shadow: 0 2px 4px rgba(0,0,0,0.08); margin-bottom: 30px; }
        .header-content { max-width: 1400px; margin: 0 auto; padding: 0 30px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 28px; color: #2c3e50; }
        .user-info { display: flex; align-items: center; gap: 15px; }
        .user-info span { color: #5a6c7d; }
        .logout { text-decoration: none; color: #e74c3c; font-weight: 500; transition: color 0.3s; }
        .logout:hover { color: #c0392b; }
        .container { max-width: 1400px; margin: 0 auto; padding: 0 30px; }
        .toolbar { display: flex; justify-content: flex-end; margin-bottom: 25px; }
        .btn { padding: 12px 24px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500; transition: all 0.3s; }
        .btn-primary { background: #667eea; color: white; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5); }
        .btn-success { background: #56ab2f; color: white; }
        .btn-warning { background: #f093fb; color: white; }
        .btn-danger { background: #fa709a; color: white; }
        .btn-secondary { background: #95a5a6; color: white; margin-left: 10px; }
        .btn-secondary:hover { background: #7f8c8d; }
        
        /* Table Styles */
        .table-container { background: white; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #667eea 0%; color: white; padding: 16px; text-align: left; font-weight: 600; text-transform: uppercase; font-size: 13px; letter-spacing: 0.5px; }
        td { padding: 16px; border-bottom: 1px solid #ecf0f1; color: #34495e; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background-color: #f8f9fa; }
        .actions { display: flex; gap: 8px; }
        .actions button { padding: 8px 16px; font-size: 13px; }
        
        /* Modal Styles */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); backdrop-filter: blur(3px); }
        .modal.active { display: flex; align-items: center; justify-content: center; animation: fadeIn 0.3s; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .modal-content { background: white; border-radius: 16px; width: 90%; max-width: 800px; max-height: 90vh; overflow-y: auto; box-shadow: 0 10px 40px rgba(0,0,0,0.2); animation: slideUp 0.3s; }
        @keyframes slideUp { from { transform: translateY(50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .modal-header { padding: 24px 30px; border-bottom: 1px solid #ecf0f1; display: flex; justify-content: space-between; align-items: center; }
        .modal-header h2 { font-size: 24px; color: #2c3e50; }
        .close { font-size: 32px; color: #95a5a6; cursor: pointer; transition: color 0.3s; line-height: 1; }
        .close:hover { color: #e74c3c; }
        .modal-body { padding: 30px; }
        
        /* Floating Label Input Styles */
        .form-row { display: grid; grid-template-columns: repeat(12, 1fr); gap: 20px; margin-bottom: 25px; }
        .form-group { position: relative; }
        .form-group.col-3 { grid-column: span 3; }
        .form-group.col-4 { grid-column: span 4; }
        .form-group.col-9 { grid-column: span 9; }
        .form-group.col-12 { grid-column: span 12; }
        
        .floating-input { width: 100%; padding: 16px 12px; border: 2px solid #e0e6ed; border-radius: 8px; font-size: 15px; transition: all 0.3s; background: #f8f9fa; }
        .floating-input:focus { outline: none; border-color: #667eea; background: white; }
        .floating-input:focus + .floating-label,
        .floating-input:not(:placeholder-shown) + .floating-label { transform:  translateX(-1px) translateY(-12px) scale(0.7); color: #667eea; }
        
        .floating-label { position: absolute; left: 12px; top: 16px; color: #95a5a6; font-size: 15px; pointer-events: none; transition: all 0.3s; transform-origin: left top; background: transparent; padding: 0 4px; }
        
        textarea.floating-input { min-height: 100px; padding-top: 20px; resize: vertical; }
        textarea.floating-input + .floating-label { top: 20px; }
        
        .modal-footer { padding: 20px 30px; border-top: 1px solid #ecf0f1; display: flex; justify-content: flex-end; gap: 10px; }
        
        /* Alert Styles */
        .alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; display: none; animation: slideDown 0.3s; }
        .alert.show { display: block; }
        @keyframes slideDown { from { transform: translateY(-20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .alert-success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .alert-error { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        
        /* Loading Spinner */
        .spinner { display: none; border: 3px solid #f3f3f3; border-top: 3px solid #667eea; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 20px auto; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <h1>상품 관리</h1>
            <div class="user-info">
                <span><?= htmlspecialchars($_SESSION['username']) ?>님 환영합니다</span>
                <a href="../logout/index.php" class="logout">로그아웃</a>
            </div>
        </div>
    </div>

    <!-- Main Container -->
    <div class="container">
        <!-- Alert Messages -->
        <div id="alertBox" class="alert"></div>
        
        <!-- Toolbar -->
        <div class="toolbar">
            <button class="btn btn-primary" onclick="openAddModal()">+ 새 상품 추가</button>
        </div>

        <!-- Loading Spinner -->
        <div class="spinner" id="loadingSpinner"></div>

        <!-- Products Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>상품 ID</th>
                        <th>상품명</th>
                        <th>가격</th>
                        <th>카테고리</th>
                        <th>진열 위치</th>
                        <th>설명</th>
                        <th>관리</th>
                    </tr>
                </thead>
                <tbody id="productTableBody">
                    <!-- Products will be loaded here via AJAX -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">상품 추가</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="productForm">
                    <input type="hidden" id="productId" name="id">
                    <input type="hidden" id="formMode" value="add">
                    
                    <!-- Row 1: 상품ID (3), 상품명 (9) -->
                    <div class="form-row">
                        <div class="form-group col-3">
                            <input type="number" id="inputId" name="id" class="floating-input" placeholder=" ">
                            <label class="floating-label">상품 ID (자동)</label>
                        </div>
                        <div class="form-group col-9">
                            <input type="text" id="inputName" name="name" class="floating-input" placeholder=" " required>
                            <label class="floating-label">상품명</label>
                        </div>
                    </div>

                    <!-- Row 2: 가격 (4), 카테고리 (4), 진열위치 (4) -->
                    <div class="form-row">
                        <div class="form-group col-4">
                            <input type="number" id="inputPrice" name="price" class="floating-input" placeholder=" " required>
                            <label class="floating-label">가격</label>
                        </div>
                        <div class="form-group col-4">
                            <input type="text" id="inputCategory" name="category" class="floating-input" placeholder=" " required>
                            <label class="floating-label">카테고리</label>
                        </div>
                        <div class="form-group col-4">
                            <input type="text" id="inputPosition" name="position" class="floating-input" placeholder=" " required>
                            <label class="floating-label">진열 위치</label>
                        </div>
                    </div>

                    <!-- Row 3: 설명 (12) -->
                    <div class="form-row">
                        <div class="form-group col-12">
                            <textarea id="inputDescription" name="description" class="floating-input" placeholder=" " required></textarea>
                            <label class="floating-label">설명</label>
                        </div>
                    </div>

                    <!-- Row 4: 비전설명 (12) -->
                    <div class="form-row">
                        <div class="form-group col-12">
                            <textarea id="inputVisionDesc" name="vision_desc" class="floating-input" placeholder=" " required></textarea>
                            <label class="floating-label">비전 설명</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">취소</button>
                <button type="button" class="btn btn-success" onclick="saveProduct()">저장</button>
            </div>
        </div>
    </div>

    <script>
        // Load products on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadProducts();
        });

        // Load Products via AJAX
        function loadProducts() {
            document.getElementById('loadingSpinner').style.display = 'block';
            
            fetch('action/getitems.php')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('loadingSpinner').style.display = 'none';
                    
                    if (data.success) {
                        displayProducts(data.products);
                    } else {
                        showAlert('상품 목록을 불러오는데 실패했습니다.', 'error');
                    }
                })
                .catch(error => {
                    document.getElementById('loadingSpinner').style.display = 'none';
                    showAlert('오류가 발생했습니다: ' + error, 'error');
                });
        }

        // Display Products in Table
        function displayProducts(products) {
            const tbody = document.getElementById('productTableBody');
            tbody.innerHTML = '';
            
            if (products.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 40px; color: #95a5a6;">등록된 상품이 없습니다.</td></tr>';
                return;
            }
            
            products.forEach(product => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${escapeHtml(product.id)}</td>
                    <td>${escapeHtml(product.name)}</td>
                    <td>${escapeHtml(product.price)}원</td>
                    <td>${escapeHtml(product.category)}</td>
                    <td>${escapeHtml(product.position)}</td>
                    <td>${escapeHtml(product.description.substring(0, 50))}...</td>
                    <td class="actions">
                        <button class="btn btn-warning" data-product='${JSON.stringify(product).replace(/'/g, "&apos;")}' onclick='editProductById(this)'>수정</button>
                        <button class="btn btn-danger" onclick="deleteProduct(${product.id})">삭제</button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        // Open Add Modal
        function openAddModal() {
            document.getElementById('modalTitle').textContent = '상품 추가';
            document.getElementById('formMode').value = 'add';
            document.getElementById('productForm').reset();
            document.getElementById('inputId').value = '';
            document.getElementById('inputId').removeAttribute('readonly');
            document.getElementById('productModal').classList.add('active');
        }

        // Edit Product by Button Element
        function editProductById(button) {
            const product = JSON.parse(button.getAttribute('data-product'));
            editProduct(product);
        }

        // Edit Product
        function editProduct(product) {
            document.getElementById('modalTitle').textContent = '상품 수정';
            document.getElementById('formMode').value = 'edit';
            document.getElementById('inputId').value = product.id;
            document.getElementById('inputId').setAttribute('readonly', 'readonly');
            document.getElementById('inputName').value = product.name;
            document.getElementById('inputPrice').value = product.price;
            document.getElementById('inputCategory').value = product.category;
            document.getElementById('inputPosition').value = product.position;
            document.getElementById('inputDescription').value = product.description;
            document.getElementById('inputVisionDesc').value = product.vision_desc;
            document.getElementById('productModal').classList.add('active');
        }

        // Close Modal
        function closeModal() {
            document.getElementById('productModal').classList.remove('active');
            document.getElementById('productForm').reset();
        }

        // Save Product (Add or Update)
        function saveProduct() {
            const form = document.getElementById('productForm');
            const formData = new FormData(form);
            const mode = document.getElementById('formMode').value;
            const url = mode === 'add' ? 'action/additem.php' : 'action/updateitem.php';
            
            fetch(url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    closeModal();
                    loadProducts();
                } else {
                    showAlert(data.message, 'error');
                }
            })
            .catch(error => {
                showAlert('오류가 발생했습니다: ' + error, 'error');
            });
        }

        // Delete Product
        function deleteProduct(id) {
            if (!confirm('정말 삭제하시겠습니까?')) return;
            
            const formData = new FormData();
            formData.append('id', id);
            
            fetch('action/deleteitem.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    loadProducts();
                } else {
                    showAlert(data.message, 'error');
                }
            })
            .catch(error => {
                showAlert('오류가 발생했습니다: ' + error, 'error');
            });
        }

        // Show Alert
        function showAlert(message, type) {
            const alertBox = document.getElementById('alertBox');
            alertBox.textContent = message;
            alertBox.className = 'alert alert-' + type + ' show';
            
            setTimeout(() => {
                alertBox.classList.remove('show');
            }, 4000);
        }

        // Escape HTML
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text).replace(/[&<>"']/g, m => map[m]);
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('productModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
