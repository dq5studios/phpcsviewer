CREATE SEQUENCE sniff_seq
    START WITH 1000
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

CREATE TABLE sniff
(
    seq integer DEFAULT nextval('sniff_seq'::regclass) NOT NULL,
    id character varying(100) NOT NULL,
    descrip text NOT NULL,
    added text NOT NULL,
    removed text
);

ALTER TABLE ONLY sniff
    ADD CONSTRAINT sniff_pkey PRIMARY KEY (seq);
