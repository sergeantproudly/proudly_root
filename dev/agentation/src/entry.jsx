import React from 'react';
import { createRoot } from 'react-dom/client';
import { Agentation } from 'agentation';

// Кнопка вызова по умолчанию слева внизу (справа у сайта чат и «наверх»).
// Панель сама хранит позицию в localStorage: x = -273 ставит свёрнутую кнопку на 20px от левого края.
// Если позицию уже меняли перетаскиванием, она остаётся как есть.
try {
	if (!localStorage.getItem('feedback-toolbar-position')) {
		localStorage.setItem('feedback-toolbar-position', JSON.stringify({ x: -273, y: window.innerHeight - 64 }));
	}
} catch (e) {}

// Панель монтируется в отдельный контейнер и не затрагивает вёрстку сайта.
// Адрес MCP-сервера (npm run mcp) задаётся через window.AGENTATION_ENDPOINT, по умолчанию режим «копировать заметки».
var host = document.createElement('div');
host.id = 'agentation-root';
document.body.appendChild(host);
createRoot(host).render(<Agentation endpoint={window.AGENTATION_ENDPOINT || undefined} />);
