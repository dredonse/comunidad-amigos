-- Coordenadas geográficas para eventos (mapas Leaflet)
ALTER TABLE events
    ADD COLUMN IF NOT EXISTS latitude  DECIMAL(10, 8) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS longitude DECIMAL(11, 8) DEFAULT NULL;
