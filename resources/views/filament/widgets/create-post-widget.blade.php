<div style="background: white; border-radius: 12px; padding: 20px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 24px;">
    <!-- Input de imagen fuera del form -->
    <input
        type="file"
        wire:model="image"
        accept="image/*"
        id="image-upload-input"
        style="display: none;"
    >

    <form wire:submit="createPost">
        <!-- Header con avatar y textarea -->
        <div style="display: flex; gap: 12px; align-items: flex-start; margin-bottom: 16px;">
            <!-- Avatar del usuario -->
            <div style="width: 48px; height: 48px; background: #3b82f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <span style="color: white; font-size: 16px; font-weight: 600;">
                    {{ auth()->check() ? strtoupper(substr(auth()->user()->name, 0, 1)) . strtoupper(substr(explode(' ', auth()->user()->name)[1] ?? '', 0, 1)) : 'U' }}
                </span>
            </div>

            <!-- Textarea principal -->
            <div style="flex: 1;">
                <textarea
                    wire:model.live="content"
                    placeholder="¿Qué quieres compartir con la comunidad de LitoPro? Promociones, trabajos terminados, consejos técnicos..."
                    style="width: 100%; border: none; outline: none; font-size: 16px; line-height: 1.5; color: #374151; resize: none; min-height: 60px; background: transparent;"
                    rows="3"
                    required
                ></textarea>

                <!-- Campo título opcional (aparece cuando se selecciona cierto tipo) -->
                @if(in_array($this->post_type, ['offer', 'request', 'equipment', 'materials']))
                    <input
                        wire:model="title"
                        type="text"
                        placeholder="Título de tu {{ $this->getPostTypes()[$this->post_type] }} (opcional)"
                        style="width: 100%; border: 1px solid #d1d5db; border-radius: 6px; padding: 8px 12px; font-size: 14px; margin-top: 8px; color: #374151;"
                    >
                @endif
            </div>
        </div>

        <!-- Vista previa de imagen -->
        @if($image)
            <div style="margin: 16px 0;">
                <div style="position: relative; display: inline-block;">
                    <img src="{{ $image->temporaryUrl() }}" style="max-width: 300px; max-height: 200px; border-radius: 8px; border: 1px solid #e5e7eb;" alt="Vista previa">
                    <button
                        type="button"
                        wire:click="removeImage"
                        style="position: absolute; top: 8px; right: 8px; width: 28px; height: 28px; background: rgba(0,0,0,0.7); color: white; border: none; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 14px;"
                        title="Quitar imagen"
                    >
                        ×
                    </button>
                </div>
            </div>
        @endif

        <!-- Separador -->
        <div style="border-top: 1px solid #f3f4f6; margin: 16px 0;"></div>

        <!-- Barra de acciones -->
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <!-- Botones de acciones izquierda -->
            <div style="display: flex; align-items: center; gap: 16px;">
                <!-- Imagen -->
                <button
                    type="button"
                    onclick="document.getElementById('image-upload-input').click()"
                    style="display: flex; align-items: center; gap: 8px; padding: 8px 12px; background: transparent; border: none; border-radius: 8px; cursor: pointer; color: #059669; font-size: 14px; font-weight: 500; transition: background-color 0.2s;"
                    onmouseover="this.style.backgroundColor='#f0fdf4'"
                    onmouseout="this.style.backgroundColor='transparent'"
                >
                    <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span>Imagen</span>
                </button>
            </div>

            <!-- Controles derecha -->
            <div style="display: flex; align-items: center; gap: 12px;">
                <!-- Selector de tipo de post -->
                <select wire:model.live="post_type" style="padding: 6px 32px 6px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; color: #374151; background: white; cursor: pointer; appearance: none; background-image: url('data:image/svg+xml;charset=US-ASCII,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 20 20%22 fill=%22%23374151%22><path fill-rule=%22evenodd%22 d=%22M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z%22 clip-rule=%22evenodd%22/></svg>'); background-repeat: no-repeat; background-position: right 8px center; background-size: 16px;">
                    @foreach($this->getPostTypes() as $type => $label)
                        <option value="{{ $type }}">{{ $label }}</option>
                    @endforeach
                </select>

                <!-- Selector de privacidad -->
                <select wire:model="is_public" style="padding: 6px 32px 6px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; color: #374151; background: white; cursor: pointer; appearance: none; background-image: url('data:image/svg+xml;charset=US-ASCII,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 20 20%22 fill=%22%23374151%22><path fill-rule=%22evenodd%22 d=%22M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z%22 clip-rule=%22evenodd%22/></svg>'); background-repeat: no-repeat; background-position: right 8px center; background-size: 16px;">
                    <option value="1">🌍 Público</option>
                    <option value="0">🔒 Privado</option>
                </select>

                <!-- Botón publicar -->
                <button
                    type="submit"
                    @if(empty(trim($this->content))) disabled @endif
                    style="display: flex; align-items: center; gap: 8px; padding: 10px 20px; background: {{ empty(trim($this->content)) ? '#9ca3af' : '#3b82f6' }}; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: {{ empty(trim($this->content)) ? 'not-allowed' : 'pointer' }}; transition: background-color 0.2s;"
                >
                    <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                    <span>Publicar</span>
                </button>
            </div>
        </div>
    </form>

    <!-- Mensaje de éxito -->
    @if (session()->has('message'))
        <div style="margin-top: 12px; padding: 12px 16px; background: #dcfce7; border: 1px solid #bbf7d0; border-radius: 8px; color: #166534; font-size: 14px;">
            {{ session('message') }}
        </div>
    @endif

    <!-- Vista previa del tipo de post seleccionado -->
    @if($this->post_type !== 'news')
        <div style="margin-top: 12px; padding: 12px 16px; background: {{ $this->post_type === 'offer' ? '#dcfce7' : ($this->post_type === 'request' ? '#fef3c7' : '#dbeafe') }}; border-radius: 8px;">
            <div style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: {{ $this->post_type === 'offer' ? '#166534' : ($this->post_type === 'request' ? '#92400e' : '#1e40af') }};">
                @if($this->post_type === 'offer')
                    <span>💼</span> <strong>Oferta de Servicios:</strong> Tu publicación aparecerá con un botón "Contactar" para que los interesados se comuniquen contigo.
                @elseif($this->post_type === 'request')
                    <span>🔍</span> <strong>Solicitud:</strong> Tu publicación aparecerá con un botón "Contactar" para facilitar respuestas.
                @elseif($this->post_type === 'equipment')
                    <span>🖨️</span> <strong>Equipo:</strong> Ideal para venta o alquiler de maquinaria de impresión.
                @elseif($this->post_type === 'materials')
                    <span>📦</span> <strong>Materiales:</strong> Para ofertas de papel, tintas y otros suministros.
                @elseif($this->post_type === 'collaboration')
                    <span>🤝</span> <strong>Colaboración:</strong> Propuestas de trabajo conjunto y alianzas.
                @endif
            </div>
        </div>
    @endif
</div>